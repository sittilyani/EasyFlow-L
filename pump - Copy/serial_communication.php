<?php
// xampp/www/iorpms/pump/serial_communication.php

class SerialCommunication {
    private static $instance = null;
    private $resource = null;
    private $port = '';
    private $isOpen = false;
    private $method = '';

    private function __construct() {
        $this->detectMethod();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detectMethod() {
        // Check available serial communication methods
        if (extension_loaded('dio')) {
            $this->method = 'dio';
            error_log("SERIAL: Using dio extension");
        } elseif (class_exists('PhpSerial')) {
            $this->method = 'php_serial';
            error_log("SERIAL: Using php_serial class");
        } else {
            $this->method = 'exec';
            error_log("SERIAL: Using exec fallback");
        }
    }

    public function open($params) {
        if ($this->isOpen) {
            $this->close();
        }

        $port = $params['port'];
        $baud = $params['baud'] ?? 9600;

        error_log("SERIAL: Opening $port at $baud baud using {$this->method}");

        switch ($this->method) {
            case 'dio':
                return $this->openDio($port, $params);
            case 'php_serial':
                return $this->openPhpSerial($port, $params);
            default:
                return $this->openExec($port, $params);
        }
    }

    private function openDio($port, $params) {
        try {
            if (!file_exists($port)) {
                error_log("SERIAL: Port $port does not exist");
                return false;
            }

            $this->resource = dio_open($port, O_RDWR | O_NOCTTY | O_NONBLOCK);

            if (!$this->resource) {
                error_log("SERIAL: dio_open failed");
                return false;
            }

            // Configure serial port
            $options = [
                'baud' => $params['baud'],
                'bits' => $params['data'],
                'stop' => $params['stop'],
                'parity' => $params['parity'] == 'none' ? 0 :
                           ($params['parity'] == 'odd' ? 1 : 2),
                'flow_control' => 0,
                'is_canonical' => 0
            ];

            dio_tcsetattr($this->resource, $options);

            $this->port = $port;
            $this->isOpen = true;
            error_log("SERIAL: dio port opened successfully");
            return true;

        } catch (Exception $e) {
            error_log("SERIAL: dio error: " . $e->getMessage());
            return false;
        }
    }

    private function openPhpSerial($port, $params) {
        try {
            require_once 'PhpSerial.php';

            $serial = new PhpSerial();
            $serial->deviceSet($port);
            $serial->confBaudRate($params['baud']);
            $serial->confParity($params['parity']);
            $serial->confCharacterLength($params['data']);
            $serial->confStopBits($params['stop']);
            $serial->confFlowControl('none');

            if ($serial->deviceOpen()) {
                $this->resource = $serial;
                $this->port = $port;
                $this->isOpen = true;
                error_log("SERIAL: php_serial port opened");
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("SERIAL: php_serial error: " . $e->getMessage());
            return false;
        }
    }

    private function openExec($port, $params) {
        // Fallback method using shell commands
        if (!file_exists($port)) {
            error_log("SERIAL: Port $port does not exist");
            return false;
        }

        // Check permissions (Windows doesn't have these functions)
        if (function_exists('is_readable') && !is_readable($port)) {
            error_log("SERIAL: Port $port is not readable");
            return false;
        }

        if (function_exists('is_writable') && !is_writable($port)) {
            error_log("SERIAL: Port $port is not writable");

            // Try to fix permissions on Linux
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                exec("sudo chmod 666 " . escapeshellarg($port) . " 2>&1", $output, $return);
                if ($return !== 0) {
                    error_log("SERIAL: Could not set permissions on $port");
                    return false;
                }
            }
        }

        // Configure port
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Linux configuration
            $sttyCmd = "stty -F " . escapeshellarg($port) .
                      " " . $params['baud'] .
                      " cs" . $params['data'] .
                      " " . $params['parity'] .
                      ($params['stop'] == 2 ? " cstopb" : " -cstopb") .
                      " -icanon min 0 time 10";
            exec($sttyCmd, $output, $return);

            if ($return !== 0) {
                error_log("SERIAL: stty configuration failed");
                return false;
            }
        }

        $this->port = $port;
        $this->isOpen = true;
        error_log("SERIAL: Port configured via exec");
        return true;
    }

    public function write($data) {
        if (!$this->isOpen) {
            error_log("SERIAL: Cannot write - port not open");
            return false;
        }

        error_log("SERIAL: Writing: " . trim($data));

        switch ($this->method) {
            case 'dio':
                $bytes = dio_write($this->resource, $data);
                break;

            case 'php_serial':
                $bytes = $this->resource->sendMessage($data);
                break;

            default:
                $bytes = $this->writeExec($data);
                break;
        }

        error_log("SERIAL: Wrote " . ($bytes ?: '0') . " bytes");
        return $bytes;
    }

    private function writeExec($data) {
        $command = "echo -n " . escapeshellarg($data) . " > " . escapeshellarg($this->port);
        exec($command, $output, $return);

        return ($return === 0) ? strlen($data) : false;
    }

    public function read($length = 1024) {
        if (!$this->isOpen) {
            return false;
        }

        switch ($this->method) {
            case 'dio':
                $data = dio_read($this->resource, $length);
                break;

            case 'php_serial':
                $data = $this->resource->readPort();
                break;

            default:
                $data = $this->readExec($length);
                break;
        }

        if ($data && trim($data)) {
            error_log("SERIAL: Read: " . trim($data));
        }

        return $data;
    }

    private function readExec($length) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - use timeout
            $command = "timeout 1 type " . escapeshellarg($this->port);
        } else {
            // Linux
            $command = "timeout 1 dd if=" . escapeshellarg($this->port) .
                      " bs=1 count=$length 2>/dev/null";
        }

        exec($command, $output, $return);

        if ($return === 0 && !empty($output)) {
            return implode("\n", $output);
        }

        return false;
    }

    public function readAll() {
        $allData = '';
        $start = time();

        while ((time() - $start) < 2) {
            $data = $this->read(1024);
            if ($data) {
                $allData .= $data;
            } else {
                usleep(100000);
            }
        }

        return $allData;
    }

    public function close() {
        if ($this->isOpen) {
            if ($this->method == 'dio' && is_resource($this->resource)) {
                dio_close($this->resource);
            } elseif ($this->method == 'php_serial' && is_object($this->resource)) {
                $this->resource->deviceClose();
            }

            $this->isOpen = false;
            $this->resource = null;
            error_log("SERIAL: Port closed");
        }
    }

    public function getPort() {
        return $this->port;
    }

    public function isOpen() {
        return $this->isOpen;
    }

    public function __destruct() {
        $this->close();
    }
}
?>