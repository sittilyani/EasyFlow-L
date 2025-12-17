<?php
// pump/test_ajax.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response FIRST
header('Content-Type: application/json');

// Start output buffering to catch any stray output
ob_start();

try {
    // Include pump manager
    require_once __DIR__ . '/pump_manager.php';

    // Get action
    $action = $_GET['action'] ?? 'status';

    // Initialize pump manager
    $pumpManager = PumpManager::getInstance();

    // Handle actions
    switch ($action) {
        case 'connection':
            $result = $pumpManager->initialize();
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Pump initialized successfully' : 'Failed to initialize pump'
            ]);
            break;

        case 'wakeup':
            $result = $pumpManager->wakeup();
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Pump woke up successfully' : 'Failed to wake up pump'
            ]);
            break;

        case 'dispense':
            $amount = floatval($_GET['amount'] ?? 0);
            if ($amount <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid amount specified'
                ]);
                break;
            }

            $result = $pumpManager->dispense($amount);
            echo json_encode([
                'success' => $result,
                'message' => $result ? "Successfully dispensed {$amount}ml" : "Failed to dispense {$amount}ml"
            ]);
            break;

        case 'status':
            $status = $pumpManager->getStatus();
            echo json_encode([
                'success' => true,
                'status' => $status
            ]);
            break;

        case 'toggle_simulation':
            // Get current status first
            $status = $pumpManager->getStatus();
            $current = $status['simulation_mode'] ?? true;
            $newMode = !$current;
            $pumpManager->setSimulationMode($newMode);
            echo json_encode([
                'success' => true,
                'message' => 'Simulation mode ' . ($newMode ? 'enabled' : 'disabled'),
                'simulation_mode' => $newMode
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action: ' . htmlspecialchars($action)
            ]);
    }

} catch (Exception $e) {
    // Clear any output
    ob_end_clean();

    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Clean any stray output
ob_end_flush();
?>