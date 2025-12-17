<?php
// xampp/www/iorpms/pump/pump_manager.php

class PumpManager {
    private static $instance = null;
    private $pumpService = null;
    private $isInitialized = false;
    private $lastActivity = 0;
    private $config = [];

    private function __construct() {
        $this->loadConfig();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig() {
        $this->config = [
            'enabled' => true,
            'simulation_mode' => true, // Set to false for real pump
            'max_daily_dosage_mg' => 120,
            'methadone_concentration' => 10,

            // Serial port configuration for XAMPP (Windows)
            'serial_port' => self::detectSerialPort(),
            'baud_rate' => 9600,
            'parity' => 'none',
            'data_bits' => 8,
            'stop_bits' => 1,
            'timeout' => 5,

            // Pump commands for MasterPlex
            'wakeup_command' => "WAKE\r\n",
            'ready_command' => "RDY?\r\n",
            'dispense_command' => "DISP %0.2f ml\r\n",
            'stop_command' => "STOP\r\n",

            // Timing (seconds)
            'wakeup_delay' => 3,
            'command_delay' => 1,
            'dispense_time_per_ml' => 2,
            'inactivity_timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 2
        ];
    }

    private static function detectSerialPort() {
        $os = strtoupper(substr(PHP_OS, 0, 3));

        if ($os === 'WIN') {
            // Windows - Laragon typically uses COM3 or COM4 for FT232R
            $possiblePorts = ['COM3', 'COM4', 'COM5', 'COM6', 'COM1', 'COM2', 'COM7', 'COM8'];

            // Also check Device Manager programmatically
            exec('wmic path Win32_SerialPort get DeviceID', $output, $return);
            if ($return === 0 && !empty($output)) {
                foreach ($output as $line) {
                    if (preg_match('/COM\d+/i', trim($line), $matches)) {
                        $possiblePorts[] = $matches[0];
                    }
                }
            }

            $possiblePorts = array_unique($possiblePorts);
            return $possiblePorts;
        } else {
            // Linux/Unix (for Laragon on Linux)
            $possiblePorts = [
                '/dev/ttyUSB0',
                '/dev/ttyUSB1',
                '/dev/ttyACM0',
                '/dev/ttyACM1',
                '/dev/ttyS0'
            ];

            return $possiblePorts;
        }
    }


    public function initialize() {
        if ($this->isInitialized) {
            return true;
        }

        error_log("PUMP MANAGER: Initializing pump system...");

        // Simulation mode check
        if ($this->config['simulation_mode']) {
            error_log("PUMP MANAGER: Running in SIMULATION MODE - no actual pump connected");
            $this->isInitialized = true;
            return true;
        }

        if (!$this->config['enabled']) {
            error_log("PUMP MANAGER: Pump operations disabled");
            return false;
        }

        // Load pump service
        require_once __DIR__ . '/pump_service.php';
        $this->pumpService = PumpService::getInstance($this->config);

        if ($this->pumpService->connect()) {
            $this->isInitialized = true;
            $this->lastActivity = time();
            error_log("PUMP MANAGER: Successfully initialized and connected");
            return true;
        }

        error_log("PUMP MANAGER: Failed to initialize pump");
        return false;
    }

    public function wakeup() {
        error_log("PUMP MANAGER: Starting wakeup sequence...");

        if (!$this->initialize()) {
            error_log("PUMP MANAGER: Cannot wake up - initialization failed");
            return false;
        }

        // Check if recently active
        if ((time() - $this->lastActivity) < $this->config['inactivity_timeout']) {
            error_log("PUMP MANAGER: Pump already active");
            $this->lastActivity = time();
            return true;
        }

        // Retry loop for wakeup
        for ($attempt = 1; $attempt <= $this->config['retry_attempts']; $attempt++) {
            error_log("PUMP MANAGER: Wakeup attempt $attempt of " . $this->config['retry_attempts']);

            $result = $this->pumpService->sendCommand('wakeup');

            if ($result) {
                error_log("PUMP MANAGER: Wakeup command accepted, waiting for pump to ready...");
                sleep($this->config['wakeup_delay']);

                // Verify pump is ready
                if ($this->pumpService->sendCommand('ready')) {
                    $this->lastActivity = time();
                    error_log("PUMP MANAGER: Pump is awake and ready");
                    return true;
                }
            }

            if ($attempt < $this->config['retry_attempts']) {
                $delay = $this->config['retry_delay'] * $attempt;
                error_log("PUMP MANAGER: Wakeup failed, retrying in {$delay} seconds...");
                sleep($delay);
            }
        }

        error_log("PUMP MANAGER: All wakeup attempts failed");
        return false;
    }

    public function dispense($amount_ml) {
        error_log("PUMP MANAGER: Request to dispense {$amount_ml}ml");

        // Validate amount
        if ($amount_ml <= 0 || $amount_ml > 100) {
            error_log("PUMP MANAGER: Invalid amount: {$amount_ml}ml");
            return false;
        }

        // Wake up pump first
        if (!$this->wakeup()) {
            error_log("PUMP MANAGER: Cannot dispense - wakeup failed");
            return false;
        }

        error_log("PUMP MANAGER: Pump awake, preparing to dispense...");

        // Retry loop for dispense
        for ($attempt = 1; $attempt <= $this->config['retry_attempts']; $attempt++) {
            error_log("PUMP MANAGER: Dispense attempt $attempt for {$amount_ml}ml");

            $result = $this->pumpService->sendCommand('dispense', $amount_ml);

            if ($result) {
                error_log("PUMP MANAGER: Dispense command accepted");

                // Calculate and wait for dispensing
                $dispenseTime = $amount_ml * $this->config['dispense_time_per_ml'];
                error_log("PUMP MANAGER: Dispensing for {$dispenseTime} seconds...");
                sleep($dispenseTime);

                // Verify completion
                if ($this->pumpService->verifyCompletion()) {
                    $this->lastActivity = time();
                    error_log("PUMP MANAGER: Dispense completed successfully");
                    return true;
                } else {
                    error_log("PUMP MANAGER: Dispense may not have completed properly");
                }
            }

            if ($attempt < $this->config['retry_attempts']) {
                $delay = $this->config['retry_delay'] * $attempt;
                error_log("PUMP MANAGER: Dispense failed, retrying in {$delay} seconds...");
                sleep($delay);
            }
        }

        error_log("PUMP MANAGER: All dispense attempts failed");
        return false;
    }

    public function getStatus() {
        $status = [
            'initialized' => $this->isInitialized,
            'simulation_mode' => $this->config['simulation_mode'],
            'enabled' => $this->config['enabled'],
            'last_activity' => date('Y-m-d H:i:s', $this->lastActivity),
            'inactive_seconds' => time() - $this->lastActivity,
            'config' => [
                'serial_port' => $this->config['serial_port'],
                'retry_attempts' => $this->config['retry_attempts']
            ]
        ];

        if ($this->pumpService) {
            $status['pump_info'] = $this->pumpService->getPumpInfo();
            $status['connected'] = $this->pumpService->isConnected();
        }

        return $status;
    }

    public function setSimulationMode($mode) {
        $this->config['simulation_mode'] = $mode;
        error_log("PUMP MANAGER: Simulation mode set to: " . ($mode ? 'ON' : 'OFF'));
    }

    public function setEnabled($enabled) {
        $this->config['enabled'] = $enabled;
        error_log("PUMP MANAGER: Pump operations " . ($enabled ? 'ENABLED' : 'DISABLED'));
    }

    public function __destruct() {
        if ($this->pumpService) {
            $this->pumpService->disconnect();
        }
    }
}
?>