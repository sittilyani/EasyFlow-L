<?php
// xampp/www/iorpms/pump/pump_service.php

require_once __DIR__ . '/serial_communication.php';

class PumpService {
    private static $instance = null;
    private $serial = null;
    private $config = [];
    private $isConnected = false;

    private function __construct($config) {
        $this->config = $config;
    }

    public static function getInstance($config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception("Configuration required for first instantiation");
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function connect() {
        if ($this->isConnected) {
            return true;
        }

        error_log("PUMP SERVICE: Attempting to connect...");

        // Simulation mode
        if ($this->config['simulation_mode']) {
            $this->isConnected = true;
            error_log("PUMP SERVICE: Simulation mode - virtual connection established");
            return true;
        }

        // Initialize serial communication
        $this->serial = SerialCommunication::getInstance();

        // Try available ports
        $ports = is_array($this->config['serial_port']) ?
                 $this->config['serial_port'] :
                 [$this->config['serial_port']];

        foreach ($ports as $port) {
            error_log("PUMP SERVICE: Trying port: $port");

            if ($this->serial->open([
                'port' => $port,
                'baud' => $this->config['baud_rate'],
                'parity' => $this->config['parity'],
                'data' => $this->config['data_bits'],
                'stop' => $this->config['stop_bits'],
                'timeout' => $this->config['timeout']
            ])) {
                error_log("PUMP SERVICE: Port opened, testing connection...");

                // Test connection
                if ($this->testConnection($port)) {
                    $this->isConnected = true;
                    error_log("PUMP SERVICE: Successfully connected to $port");
                    return true;
                }

                $this->serial->close();
            }
        }

        error_log("PUMP SERVICE: Could not connect to any port");
        return false;
    }

    private function testConnection($port) {
        if ($this->config['simulation_mode']) {
            return true;
        }

        try {
            // Clear buffer
            $this->serial->readAll();

            // Send wakeup command
            $wakeupResult = $this->sendRawCommand($this->config['wakeup_command'], 2);

            if ($wakeupResult !== false) {
                error_log("PUMP SERVICE: Wakeup response: " . trim($wakeupResult));
                return true;
            }

            // Try ready command
            $readyResult = $this->sendRawCommand($this->config['ready_command'], 1);

            if ($readyResult !== false && stripos($readyResult, 'READY') !== false) {
                error_log("PUMP SERVICE: Ready response received");
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("PUMP SERVICE: Connection test error: " . $e->getMessage());
            return false;
        }
    }

    public function sendCommand($type, $param = null) {
        if (!$this->isConnected && !$this->config['simulation_mode']) {
            if (!$this->connect()) {
                error_log("PUMP SERVICE: Not connected, cannot send command");
                return false;
            }
        }

        // Build command
        switch ($type) {
            case 'wakeup':
                $command = $this->config['wakeup_command'];
                $waitTime = 2;
                break;

            case 'ready':
                $command = $this->config['ready_command'];
                $waitTime = 1;
                break;

            case 'dispense':
                if ($param === null) {
                    error_log("PUMP SERVICE: Dispense requires amount parameter");
                    return false;
                }
                $command = sprintf($this->config['dispense_command'], $param);
                $waitTime = 1;
                break;

            case 'stop':
                $command = $this->config['stop_command'];
                $waitTime = 1;
                break;

            default:
                error_log("PUMP SERVICE: Unknown command type: $type");
                return false;
        }

        error_log("PUMP SERVICE: Sending command: " . trim($command));

        if ($this->config['simulation_mode']) {
            error_log("PUMP SERVICE: Simulation - command would be: " . trim($command));
            sleep(1);
            return true;
        }

        $response = $this->sendRawCommand($command, $waitTime);

        if ($response !== false) {
            error_log("PUMP SERVICE: Response: " . trim($response));
            return true;
        }

        error_log("PUMP SERVICE: No response from pump");
        return false;
    }

    private function sendRawCommand($command, $waitTime = 1) {
        if ($this->config['simulation_mode']) {
            return "SIMULATED: " . trim($command);
        }

        if (!$this->serial || !$this->isConnected) {
            return false;
        }

        try {
            // Write command
            $bytes = $this->serial->write($command);

            if ($bytes != strlen($command)) {
                error_log("PUMP SERVICE: Failed to write full command");
                return false;
            }

            // Wait for response
            sleep($waitTime);

            // Read response
            $response = $this->serial->read(1024);

            return $response ?: "No response";

        } catch (Exception $e) {
            error_log("PUMP SERVICE: Error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyCompletion() {
        if ($this->config['simulation_mode']) {
            return true;
        }

        // Send ready check
        return $this->sendCommand('ready');
    }

    public function getPumpInfo() {
        if ($this->config['simulation_mode']) {
            return [
                'status' => 'Simulation Mode',
                'model' => 'MasterPlex Simulator',
                'version' => '1.0'
            ];
        }

        return [
            'status' => $this->isConnected ? 'Connected' : 'Disconnected',
            'model' => 'MasterPlex Pump',
            'port' => $this->serial ? $this->serial->getPort() : 'Not connected'
        ];
    }

    public function isConnected() {
        return $this->isConnected;
    }

    public function disconnect() {
        if ($this->serial) {
            $this->serial->close();
        }
        $this->isConnected = false;
        error_log("PUMP SERVICE: Disconnected");
    }

    public function __destruct() {
        $this->disconnect();
    }
}
?>