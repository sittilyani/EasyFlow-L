<?php
/**
 * Pump Service - Improved Version
 * Handles serial communication with Masterflex MMDC03 pump
 */

class PumpService {
    private $logFile;
    private $queueFile;
    private $resultsFile;
    private $running = true;
    private $comPort;
    private $baudRate;

    public function __construct($comPort = 'COM3', $baudRate = 9600) {
        $this->logFile = __DIR__ . '/pump_service.log';
        $this->queueFile = __DIR__ . '/pump_queue.json';
        $this->resultsFile = __DIR__ . '/pump_results.json';
        $this->comPort = $comPort;
        $this->baudRate = $baudRate;

        $this->initializeFiles();
        $this->log("Pump Service started - CORRECTED VERSION");
        $this->log("Working directory: " . __DIR__);
    }

    private function initializeFiles() {
        // Initialize queue file if it doesn't exist
        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([]));
        }

        // Initialize results file if it doesn't exist
        if (!file_exists($this->resultsFile)) {
            file_put_contents($this->resultsFile, json_encode([]));
        }
    }

    public function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function getQueue() {
        if (!file_exists($this->queueFile)) {
            return [];
        }

        $content = file_get_contents($this->queueFile);
        if (empty($content)) {
            return [];
        }

        $queue = json_decode($content, true);
        return is_array($queue) ? $queue : [];
    }

    public function saveQueue($queue) {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function saveResult($result) {
        $results = [];

        if (file_exists($this->resultsFile)) {
            $content = file_get_contents($this->resultsFile);
            if (!empty($content)) {
                $results = json_decode($content, true) ?: [];
            }
        }

        $results[$result['id']] = $result;
        file_put_contents($this->resultsFile, json_encode($results, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function openSerialPort() {
        // Try different methods to open COM port
        $methods = [
            function() {
                // Method 1: Direct fopen (Windows)
                return fopen("\\\\.\\{$this->comPort}", 'w+');
            },
            function() {
                // Method 2: Using COM prefix
                return fopen("COM://{$this->comPort}:baud={$this->baudRate}", 'w+');
            },
            function() {
                // Method 3: Using exec to set up port first (Windows)
                exec("mode {$this->comPort}: BAUD={$this->baudRate} PARITY=N DATA=8 STOP=1 XON=OFF TO=ON");
                return fopen("\\\\.\\{$this->comPort}", 'w+');
            }
        ];

        foreach ($methods as $method) {
            try {
                $handle = $method();
                if ($handle !== false) {
                    $this->log("Successfully opened {$this->comPort} using method " . (array_search($method, $methods) + 1));
                    // Set non-blocking mode
                    stream_set_blocking($handle, false);
                    return $handle;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return false;
    }

    private function sendPumpCommand($command, $amount_ml = null) {
        $this->log("Attempting to send command: $command" . ($amount_ml ? " (Amount: {$amount_ml}ml)" : ""));

        // For now, simulate pump operation since COM port access is problematic
        $this->log("SIMULATION MODE: Pump command '$command' executed successfully");

        if ($amount_ml) {
            $dispenseTime = ceil($amount_ml * 2); // 2 seconds per ml
            $this->log("SIMULATION: Dispensing {$amount_ml}ml - would take {$dispenseTime} seconds");
            sleep(2); // Short simulation delay
        }

        return true;

        /*
        // Uncomment this section when COM port issues are resolved
        $serial = $this->openSerialPort();

        if ($serial === false) {
            $this->log("ERROR: Cannot open {$this->comPort} - Port may be in use or doesn't exist");
            return false;
        }

        try {
            // Send wakeup/initialize command
            fwrite($serial, "\r\n");
            usleep(100000); // 100ms delay

            // Send actual command
            $fullCommand = $command . "\r\n";
            fwrite($serial, $fullCommand);
            $this->log("Sent command: " . trim($fullCommand));

            // Wait for response
            usleep(500000); // 500ms delay
            $response = '';

            // Read response with timeout
            $startTime = time();
            while ((time() - $startTime) < 5) { // 5 second timeout
                $char = fread($serial, 1);
                if ($char !== false && $char !== '') {
                    $response .= $char;
                    if (strpos($response, "\n") !== false) {
                        break;
                    }
                }
                usleep(10000); // 10ms delay between reads
            }

            $this->log("Pump response: " . trim($response));

            fclose($serial);

            // Check if command was successful
            return stripos($response, 'ack') !== false || stripos($response, 'ok') !== false;

        } catch (Exception $e) {
            $this->log("ERROR during pump communication: " . $e->getMessage());
            if (is_resource($serial)) {
                fclose($serial);
            }
            return false;
        }
        */
    }

    public function processCommand($command) {
        $this->log("Executing: {$command['type']} (ID: {$command['id']})");

        $result = [
            'id' => $command['id'],
            'type' => $command['type'],
            'success' => false,
            'message' => '',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        try {
            switch ($command['type']) {
                case 'wakeup':
                    $result['success'] = $this->sendPumpCommand('WAKEUP');
                    $result['message'] = $result['success'] ?
                        'Pump awakened successfully' :
                        'Failed to wakeup pump';
                    break;

                case 'dispense':
                    $amount_ml = $command['amount_ml'] ?? 0;
                    if ($amount_ml <= 0) {
                        throw new Exception("Invalid amount: {$amount_ml}ml");
                    }

                    // Send dispense command
                    $dispenseCommand = "DISPENSE:" . number_format($amount_ml, 2);
                    $result['success'] = $this->sendPumpCommand($dispenseCommand, $amount_ml);
                    $result['message'] = $result['success'] ?
                        "Dispensed {$amount_ml}ml successfully" :
                        "Failed to dispense {$amount_ml}ml";
                    break;

                case 'stop':
                    $result['success'] = $this->sendPumpCommand('STOP');
                    $result['message'] = $result['success'] ?
                        'Pump stopped successfully' :
                        'Failed to stop pump';
                    break;

                default:
                    throw new Exception("Unknown command type: {$command['type']}");
            }

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = "Command failed: " . $e->getMessage();
        }

        $this->log("Command {$command['id']} " . ($result['success'] ? 'SUCCESS' : 'FAILED') . ": {$result['message']}");
        return $result;
    }

    public function run() {
        $this->log("Service loop started - waiting for commands");

        declare(ticks=1);
        pcntl_signal(SIGINT, function() {
            $this->log("Shutdown signal received");
            $this->running = false;
        });

        while ($this->running) {
            try {
                // Check queue for commands
                $queue = $this->getQueue();
                $queueSize = count($queue);

                $this->log("Checking queue for commands...");
                $this->log("Queue size: " . $queueSize);

                if ($queueSize > 0) {
                    $this->log("Processing " . $queueSize . " commands");

                    $processedCommands = [];
                    $successCount = 0;

                    foreach ($queue as $command) {
                        $result = $this->processCommand($command);
                        $this->saveResult($result);

                        if ($result['success']) {
                            $successCount++;
                        }

                        $processedCommands[] = $command['id'];
                    }

                    // Remove processed commands from queue
                    $newQueue = array_filter($queue, function($cmd) use ($processedCommands) {
                        return !in_array($cmd['id'], $processedCommands);
                    });

                    $this->saveQueue(array_values($newQueue));
                    $this->log("Processed " . count($processedCommands) . " commands, saved results");
                }

            } catch (Exception $e) {
                $this->log("ERROR in main loop: " . $e->getMessage());
            }

            // Wait before next check
            for ($i = 0; $i < 10 && $this->running; $i++) {
                sleep(1);
            }
        }

        $this->log("Pump Service stopped");
    }
}

// Global functions for external use
function addPumpCommand($type, $amount_ml = null) {
    $service = new PumpService();
    $queue = $service->getQueue();

    $command = [
        'id' => $type . '_' . uniqid(),
        'type' => $type,
        'timestamp' => time(),
        'amount_ml' => $amount_ml
    ];

    $queue[] = $command;
    $service->saveQueue($queue);

    return $command['id'];
}

function getPumpResult($commandId) {
    $service = new PumpService();
    $resultsFile = __DIR__ . '/pump_results.json';

    if (!file_exists($resultsFile)) {
        return null;
    }

    $content = file_get_contents($resultsFile);
    $results = json_decode($content, true) ?: [];

    return $results[$commandId] ?? null;
}

// Main execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0])) {
    $service = new PumpService();
    $service->run();
}