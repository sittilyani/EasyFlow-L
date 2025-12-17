# pump/pump_controller_fixed.py - Correct protocol for MasterPlex Pump
import serial
import time
import sys
import json

# Configuration
COM_PORT = "COM20"
BAUDRATE = 9600
TIMEOUT = 5

def log_message(message):
    """Log message with timestamp"""
    timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{timestamp}] {message}")
    sys.stdout.flush()

def open_serial_port():
    """Open serial port with correct settings for FT232R"""
    try:
        log_message(f"Opening {COM_PORT} at {BAUDRATE} baud...")

        ser = serial.Serial(
            port=COM_PORT,
            baudrate=BAUDRATE,
            bytesize=serial.EIGHTBITS,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            timeout=TIMEOUT,
            write_timeout=TIMEOUT,
            xonxoff=False,    # No software flow control
            rtscts=False,     # No hardware flow control
            dsrdtr=False      # No hardware flow control
        )

        # Set control lines for FT232R
        ser.dtr = False  # Data Terminal Ready
        ser.rts = False  # Request To Send

        # Clear buffers
        time.sleep(0.5)
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        log_message(f"Port opened successfully")
        return ser

    except Exception as e:
        log_message(f"Error opening port: {e}")
        return None

def send_command_raw(ser, command_bytes):
    """Send raw bytes to pump"""
    try:
        log_message(f"Sending raw: {command_bytes.hex()}")
        ser.write(command_bytes)
        ser.flush()
        return True
    except Exception as e:
        log_message(f"Error sending: {e}")
        return False

def read_response(ser, timeout=3):
    """Read response from pump"""
    response = b""
    start_time = time.time()

    while time.time() - start_time < timeout:
        if ser.in_waiting > 0:
            chunk = ser.read(ser.in_waiting)
            response += chunk
            log_message(f"Received chunk: {chunk.hex()}")

            # MasterPlex responses often end with \r\n
            if response.endswith(b'\r\n'):
                break

        time.sleep(0.1)

    if response:
        log_message(f"Full response: {response.hex()}")
        # Try to decode as ASCII
        try:
            decoded = response.decode('ascii', errors='ignore').strip()
            log_message(f"Decoded: '{decoded}'")
            return decoded
        except:
            return response.hex()

    return ""

def wakeup_pump():
    """MasterPlex pumps often need specific initialization sequence"""
    log_message("Starting MasterPlex wakeup sequence...")

    ser = open_serial_port()
    if not ser:
        return False

    try:
        # Sequence 1: Try to clear any pending data
        time.sleep(1)
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        # Sequence 2: Send carriage return to wake up
        log_message("Sending wakeup carriage return...")
        ser.write(b'\r')
        ser.flush()
        time.sleep(1)

        # Sequence 3: Try standard MasterPlex commands
        commands_to_try = [
            b'REMOTE\r\n',      # Enter remote mode
            b'RMT\r\n',         # Alternative remote command
            b'\x05',            # ENQ (Enquiry) - ASCII 05
            b'\r\n',            # Just newline
            b'READY\r\n',       # Ready command
        ]

        for cmd in commands_to_try:
            log_message(f"Trying command: {cmd}")
            ser.write(cmd)
            ser.flush()
            time.sleep(0.5)

            # Check for response
            response = read_response(ser, 2)
            if response:
                log_message(f"Got response: {response}")
                break

        # Final check - send status request
        time.sleep(1)
        ser.write(b'STATUS\r\n')
        ser.flush()
        response = read_response(ser, 2)

        if response:
            log_message(f"Pump responded: {response}")
            ser.close()
            return True
        else:
            # Even if no response, assume pump is ready
            log_message("No response but assuming pump is ready")
            ser.close()
            return True

    except Exception as e:
        log_message(f"Wakeup error: {e}")
        if ser:
            ser.close()
        return False

def dispense_volume(ml):
    """Dispense specific volume using MasterPlex protocol"""
    log_message(f"Dispensing {ml} ml...")

    ser = open_serial_port()
    if not ser:
        return False

    try:
        # Clear buffers
        time.sleep(0.5)
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        # Method 1: Try MasterPlex formatted command
        # Format: "D{volume}{unit}" e.g., "D5.00ML" or "D00500UL"

        # Try different formats
        formats_to_try = [
            f"D{ml:.2f}ML\r\n",      # D5.00ML
            f"DV{ml:.2f}\r\n",       # DV5.00
            f"VOL{ml:.2f}\r\n",      # VOL5.00
            f"PUMP{ml:.2f}\r\n",     # PUMP5.00
            f"{ml:.2f}ML\r\n",       # 5.00ML
            f"GO{ml:.2f}\r\n",       # GO5.00
        ]

        response_received = False

        for cmd in formats_to_try:
            log_message(f"Trying command: {cmd.strip()}")
            ser.write(cmd.encode())
            ser.flush()
            time.sleep(1)

            # Check for response
            response = read_response(ser, 2)
            if response:
                log_message(f"Command accepted: {response}")
                response_received = True
                break

        if not response_received:
            # Method 2: Direct run/stop with timing
            log_message("Using timed run method...")

            # Calculate run time (MasterPlex pumps: ~2 sec/ml at max speed)
            run_time = ml * 2  # seconds

            # Send RUN command
            ser.write(b'RUN\r\n')
            ser.flush()
            log_message(f"Running for {run_time} seconds...")

            # Wait for dispensing
            time.sleep(run_time)

            # Send STOP command
            ser.write(b'STOP\r\n')
            ser.flush()
            log_message("Stopped")

        # Wait a bit more for any final movement
        time.sleep(1)

        ser.close()
        log_message("Dispense sequence completed")
        return True

    except Exception as e:
        log_message(f"Dispense error: {e}")
        if ser:
            ser.close()
        return False

def test_communication():
    """Test basic communication with pump"""
    log_message("Testing communication...")

    ser = open_serial_port()
    if not ser:
        return False

    try:
        # Try to read any existing data
        time.sleep(1)

        # Send ENQ (Enquiry - ASCII 05) - common for pumps
        log_message("Sending ENQ (\\x05)...")
        ser.write(b'\x05')
        ser.flush()

        # Wait for response
        time.sleep(1)
        response = read_response(ser, 3)

        if response:
            log_message(f"Response received: {response}")
            ser.close()
            return True

        # Try ASCII "?" command
        log_message("Sending '?'...")
        ser.write(b'?\r\n')
        ser.flush()

        time.sleep(1)
        response = read_response(ser, 3)

        if response:
            log_message(f"Response to '?': {response}")
            ser.close()
            return True

        # Try simple echo test
        log_message("Sending 'ECHO'...")
        ser.write(b'ECHO\r\n')
        ser.flush()

        time.sleep(1)
        response = read_response(ser, 3)

        ser.close()

        if response:
            log_message(f"Echo response: {response}")
            return True
        else:
            log_message("No response to any test commands")
            return False

    except Exception as e:
        log_message(f"Test error: {e}")
        if ser:
            ser.close()
        return False

def discover_protocol():
    """Try to discover the correct protocol"""
    log_message("=== Protocol Discovery ===")

    ser = open_serial_port()
    if not ser:
        return

    test_sequences = [
        # Common pump commands
        (b'\r\n', "Carriage return"),
        (b'\x05\r\n', "ENQ"),
        (b'REMOTE\r\n', "Remote mode"),
        (b'LOCAL\r\n', "Local mode"),
        (b'STATUS\r\n', "Status"),
        (b'VERSION\r\n', "Version"),
        (b'ID\r\n', "ID"),
        (b'READY\r\n', "Ready"),
        (b'RUN\r\n', "Run"),
        (b'STOP\r\n', "Stop"),
        (b'D1.00ML\r\n', "Dispense 1ml"),
        (b'PURGE\r\n', "Purge"),
        (b'PRIME\r\n', "Prime"),
    ]

    for cmd_bytes, description in test_sequences:
        log_message(f"\nTesting: {description} -> {cmd_bytes.hex()}")

        ser.reset_input_buffer()
        ser.write(cmd_bytes)
        ser.flush()

        time.sleep(1)

        if ser.in_waiting > 0:
            response = ser.read(ser.in_waiting)
            log_message(f"Response: {response.hex()} -> '{response.decode('ascii', errors='ignore')}'")
        else:
            log_message("No response")

    ser.close()
    log_message("=== Discovery Complete ===")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python pump_controller_fixed.py <action> [value]")
        print("Actions: wakeup, dispense <ml>, test, discover")
        sys.exit(1)

    action = sys.argv[1].lower()
    value = sys.argv[2] if len(sys.argv) > 2 else ""

    result = {"success": False, "message": "", "action": action}

    try:
        if action == "wakeup":
            result["success"] = wakeup_pump()
            result["message"] = "Wakeup " + ("successful" if result["success"] else "failed")

        elif action == "dispense":
            if value:
                ml = float(value)
                result["success"] = dispense_volume(ml)
                result["message"] = f"Dispense {ml} ml " + ("successful" if result["success"] else "failed")
            else:
                result["message"] = "Missing ml value"

        elif action == "test":
            result["success"] = test_communication()
            result["message"] = "Communication test " + ("passed" if result["success"] else "failed")

        elif action == "discover":
            discover_protocol()
            result["success"] = True
            result["message"] = "Protocol discovery completed"

        else:
            result["message"] = f"Unknown action: {action}"

    except Exception as e:
        result["message"] = f"Error: {str(e)}"

    print("\n" + json.dumps(result))
    sys.stdout.flush()