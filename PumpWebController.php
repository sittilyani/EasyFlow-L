<?php
// PumpWebController.php - Complete version with all methods
class PumpWebController {
    private $queueFile = 'pump_commands.json';
    private $resultFile = 'pump_results.json';
    private $timeout = 30;

    public function __construct($timeout = 30) {
        $this->timeout = $timeout;

        // Ensure queue files exist
        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([]));
        }
        if (!file_exists($this->resultFile)) {
            file_put_contents($this->resultFile, json_encode([]));
        }
    }

    public function wakeUp() {
        $commandId = uniqid('wakeup_');
        $command = [
            'action' => 'wakeup',
            'timestamp' => time(),
            'description' => "Wake up pump"
        ];

        return $this->executeCommand($commandId, $command);
    }

    public function dispense($amount_ml) {
        $commandId = uniqid('dispense_');
        $command = [
            'action' => 'dispense',
            'amount_ml' => floatval($amount_ml),
            'timestamp' => time(),
            'description' => "Dispense {$amount_ml} ml"
        ];

        return $this->executeCommand($commandId, $command);
    }

    public function stop() {
        $commandId = uniqid('stop_');
        $command = [
            'action' => 'stop',
            'timestamp' => time(),
            'description' => "Emergency stop"
        ];

        return $this->executeCommand($commandId, $command);
    }

    public function testConnection() {
        $commandId = uniqid('test_');
        $command = [
            'action' => 'test',
            'timestamp' => time(),
            'description' => "Test pump connection"
        ];

        return $this->executeCommand($commandId, $command);
    }

    private function executeCommand($commandId, $command) {
        // Add command to queue
        $this->addToQueue($commandId, $command);

        // Wait for result
        return $this->waitForResult($commandId);
    }

    private function addToQueue($commandId, $command) {
        $maxWait = 5;
        $startTime = time();

        while ((time() - $startTime) < $maxWait) {
            $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];

            if (!isset($queue[$commandId])) {
                $queue[$commandId] = $command;

                if (file_put_contents($this->queueFile, json_encode($queue), LOCK_EX)) {
                    return true;
                }
            }

            usleep(100000);
        }

        throw new Exception("Could not add command to queue");
    }

    private function waitForResult($commandId) {
        $startTime = time();

        while ((time() - $startTime) < $this->timeout) {
            $results = json_decode(file_get_contents($this->resultFile), true) ?? [];

            if (isset($results[$commandId])) {
                $result = $results[$commandId];

                // Remove from results
                unset($results[$commandId]);
                file_put_contents($this->resultFile, json_encode($results), LOCK_EX);

                if ($result['status'] === 'success') {
                    return $result['result'];
                } else {
                    throw new Exception("Pump error: " . $result['error']);
                }
            }

            usleep(500000);
        }

        throw new Exception("Timeout waiting for pump response");
    }

    public function getQueueStatus() {
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        $results = json_decode(file_get_contents($this->resultFile), true) ?? [];

        return [
            'queued_commands' => count($queue),
            'pending_results' => count($results),
            'queued_command_ids' => array_keys($queue),
            'pending_result_ids' => array_keys($results)
        ];
    }
}
?>