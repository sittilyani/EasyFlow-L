#!/usr/bin/env python3
"""
Cole-Parmer Masterflex L/S Pump Controller
Model: 2000-0078
Protocol: ASCII with CR termination
"""

import serial
import time
import sys
import json
import os
from datetime import datetime
import argparse

class MasterflexPump:
    def __init__(self, port='COM20'):
        self.port = port
        self.baudrate = 9600  # Cole-Parmer standard
        self.serial = None
        self.config_file = 'pump_config.json'
        self.config = self.load_config()

    def load_config(self):
        """Load pump configuration from file"""
        default_config = {
            'tubing_diameter': 14.7,  # mm
            'default_rate': 10.0,     # ml/min
            'direction': 'INF',       # INF=infuse (out), WDR=withdraw (in)
            'timeout': 3.0,
            'max_volume': 1000.0      # ml
        }

        if os.path.exists(self.config_file):
            try:
                with open(self.config_file, 'r') as f:
                    return {**default_config, **json.load(f)}
            except:
                pass
        return default_config

    def save_config(self):
        """Save pump configuration to file"""
        try:
            with open(self.config_file, 'w') as f:
                json.dump(self.config, f, indent=2)
            return True
        except:
            return False

    def connect(self):
        """Establish serial connection"""
        try:
            self.serial = serial.Serial(
                port=self.port,
                baudrate=self.baudrate,
                bytesize=serial.EIGHTBITS,
                parity=serial.PARITY_NONE,
                stopbits=serial.STOPBITS_ONE,
                timeout=self.config['timeout'],
                write_timeout=self.config['timeout'],
                xonxoff=False,
                rtscts=False,
                dsrdtr=False
            )
            time.sleep(0.5)  # Wait for connection
            print(f"[{datetime.now()}] Connected to {self.port} at {self.baudrate} baud")
            return True
        except Exception as e:
            print(f"[{datetime.now()}] Connection error: {e}")
            return False

    def disconnect(self):
        """Close serial connection"""
        if self.serial and self.serial.is_open:
            self.serial.close()
            print(f"[{datetime.now()}] Disconnected")

    def send_command(self, command, wait_response=True, retries=2):
        """Send command with CR termination and get response"""
        if not self.serial or not self.serial.is_open:
            if not self.connect():
                return None

        for attempt in range(retries):
            try:
                # Clear input buffer
                self.serial.reset_input_buffer()

                # Add carriage return termination
                full_cmd = command.strip() + '\r'
                print(f"[{datetime.now()}] Sending ({attempt+1}/{retries}): {repr(full_cmd)}")

                # Send command
                self.serial.write(full_cmd.encode('ascii'))
                self.serial.flush()

                if not wait_response:
                    return "OK"

                # Read response
                response = b""
                start_time = time.time()

                while time.time() - start_time < self.config['timeout']:
                    if self.serial.in_waiting:
                        byte = self.serial.read(1)
                        response += byte

                        # Responses end with CR, LF, or >
                        if byte in [b'\r', b'\n', b'>']:
                            break
                    time.sleep(0.01)

                if response:
                    decoded = response.decode('ascii', errors='ignore').strip()
                    # Remove command echo if present
                    if decoded.startswith(command):
                        decoded = decoded[len(command):].strip()
                    print(f"[{datetime.now()}] Received: {repr(decoded)}")
                    return decoded
                else:
                    print(f"[{datetime.now()}] No response")
                    time.sleep(0.5)  # Wait before retry

            except Exception as e:
                print(f"[{datetime.now()}] Command error: {e}")
                if attempt < retries - 1:
                    time.sleep(0.5)
                    continue

        return None

    def initialize(self):
        """Initialize pump with correct settings"""
        print(f"[{datetime.now()}] Initializing pump...")

        # Enter remote mode
        print(f"[{datetime.now()}] Entering remote mode...")
        self.send_command("REM", wait_response=False)
        time.sleep(0.2)

        # Clear any errors
        self.send_command("CLR", wait_response=False)
        time.sleep(0.2)

        # Set tubing diameter
        dia_cmd = f"DIA {self.config['tubing_diameter']}"
        self.send_command(dia_cmd, wait_response=False)
        time.sleep(0.2)

        # Reset volume counter
        self.send_command("VOL 0", wait_response=False)
        time.sleep(0.2)

        # Set direction
        dir_cmd = f"DIR {self.config['direction']}"
        self.send_command(dir_cmd, wait_response=False)
        time.sleep(0.2)

        # Set rate to 0
        self.send_command("RAT 0", wait_response=False)
        time.sleep(0.2)

        print(f"[{datetime.now()}] Initialization complete")
        return True

    def start_pump(self, rate=None):
        """Start the pump at specified rate"""
        if rate is None:
            rate = self.config['default_rate']

        print(f"[{datetime.now()}] Starting pump at {rate} ml/min...")

        # Set rate
        rat_response = self.send_command(f"RAT {rate}")
        time.sleep(0.2)

        # Start pump
        run_response = self.send_command("RUN")

        if (rat_response and "ERR" not in rat_response.upper() and
            run_response and "ERR" not in run_response.upper()):
            print(f"[{datetime.now()}] Pump started successfully")
            return True
        else:
            print(f"[{datetime.now()}] Failed to start pump")
            return False

    def stop_pump(self):
        """Stop the pump"""
        print(f"[{datetime.now()}] Stopping pump...")

        response = self.send_command("STOP")
        time.sleep(0.2)

        # Also set rate to 0
        self.send_command("RAT 0", wait_response=False)

        print(f"[{datetime.now()}] Pump stopped")
        return True

    def dispense_volume(self, volume_ml, rate=None):
        """Dispense a specific volume"""
        print(f"[{datetime.now()}] Dispensing {volume_ml} ml...")

        if volume_ml <= 0 or volume_ml > self.config['max_volume']:
            print(f"[{datetime.now()}] Invalid volume: {volume_ml} ml")
            return False

        if rate is None:
            # Auto-calculate reasonable rate
            rate = min(50.0, max(1.0, volume_ml * 2))

        # 1. Stop if running
        self.stop_pump()
        time.sleep(0.3)

        # 2. Reset volume counter
        self.send_command("VOL 0", wait_response=False)
        time.sleep(0.2)

        # 3. Set target volume
        self.send_command(f"VOL {volume_ml}", wait_response=False)
        time.sleep(0.2)

        # 4. Set rate
        self.send_command(f"RAT {rate}", wait_response=False)
        time.sleep(0.2)

        # 5. Set direction to infuse
        self.send_command(f"DIR {self.config['direction']}", wait_response=False)
        time.sleep(0.2)

        # 6. Start pump
        if self.start_pump(rate):
            # Wait for completion (estimated)
            estimated_time = (volume_ml / rate) * 60  # seconds
            print(f"[{datetime.now()}] Estimated time: {estimated_time:.1f} seconds")

            # Monitor for completion
            start_time = time.time()
            last_volume = 0

            while time.time() - start_time < estimated_time + 30:  # +30 second buffer
                time.sleep(1)

                # Check volume every 5 seconds
                if int(time.time() - start_time) % 5 == 0:
                    vol_response = self.send_command("VOL?")
                    if vol_response:
                        try:
                            parts = vol_response.split()
                            if parts:
                                current_vol = float(parts[0])
                                print(f"[{datetime.now()}] Current volume: {current_vol:.1f} ml")

                                if current_vol >= volume_ml:
                                    print(f"[{datetime.now()}] Target volume reached")
                                    break
                                elif current_vol == last_volume:
                                    print(f"[{datetime.now()}] Volume not changing - check pump")
                                last_volume = current_vol
                        except:
                            pass

            # Stop after completion or timeout
            self.stop_pump()
            print(f"[{datetime.now()}] Dispense complete")
            return True

        return False

    def get_status(self):
        """Get pump status"""
        print(f"[{datetime.now()}] Getting pump status...")

        status = {}

        # Get various status parameters
        queries = [
            ("?", "Status"),
            ("RAT?", "Current Rate"),
            ("VOL?", "Current Volume"),
            ("DIR?", "Direction"),
            ("DIA?", "Tubing Diameter"),
            ("ALM?", "Alarms")
        ]

        for cmd, name in queries:
            response = self.send_command(cmd)
            status[name] = response if response else "No response"
            time.sleep(0.1)

        return status

    def test_connection(self):
        """Test if pump responds"""
        print(f"[{datetime.now()}] Testing connection to {self.port}...")

        if not self.connect():
            return {"connected": False, "message": "Failed to open port"}

        # Try to get status
        response = self.send_command("?")

        if response:
            self.disconnect()
            return {"connected": True, "message": f"Pump responds: {response}"}
        else:
            # Try alternative commands
            alt_commands = ["*IDN?", "VER", "V"]
            for cmd in alt_commands:
                response = self.send_command(cmd)
                if response:
                    self.disconnect()
                    return {"connected": True, "message": f"Responds to {cmd}: {response}"}

            self.disconnect()
            return {"connected": False, "message": "No response to any command"}

    def run_command(self, command):
        """Run a raw command and return response"""
        print(f"[{datetime.now()}] Running command: {command}")

        if not self.connect():
            return None

        response = self.send_command(command)
        self.disconnect()

        return response

def main():
    parser = argparse.ArgumentParser(description='Cole-Parmer Masterflex Pump Controller')
    parser.add_argument('action', choices=['test', 'init', 'start', 'stop', 'dispense', 'status', 'command', 'config'])
    parser.add_argument('--amount', type=float, help='Volume to dispense in ml')
    parser.add_argument('--rate', type=float, help='Rate in ml/min')
    parser.add_argument('--port', default='COM20', help='Serial port')
    parser.add_argument('--command', help='Raw command to send')
    parser.add_argument('--param', help='Configuration parameter in format key=value')

    args = parser.parse_args()
    pump = MasterflexPump(port=args.port)

    result = {"success": False, "action": args.action}

    try:
        if args.action == 'test':
            test_result = pump.test_connection()
            result["success"] = test_result["connected"]
            result["message"] = test_result["message"]

        elif args.action == 'init':
            pump.connect()
            pump.initialize()
            pump.disconnect()
            result["success"] = True
            result["message"] = "Pump initialized"

        elif args.action == 'start':
            pump.connect()
            if pump.start_pump(args.rate):
                result["success"] = True
                result["message"] = "Pump started"
            else:
                result["message"] = "Failed to start pump"
            pump.disconnect()

        elif args.action == 'stop':
            pump.connect()
            pump.stop_pump()
            pump.disconnect()
            result["success"] = True
            result["message"] = "Pump stopped"

        elif args.action == 'dispense':
            if not args.amount:
                result["message"] = "Amount required for dispense"
            else:
                pump.connect()
                pump.initialize()
                if pump.dispense_volume(args.amount, args.rate):
                    result["success"] = True
                    result["message"] = f"Dispensed {args.amount} ml"
                else:
                    result["message"] = "Dispense failed"
                pump.disconnect()

        elif args.action == 'status':
            pump.connect()
            status = pump.get_status()
            pump.disconnect()
            result["success"] = True
            result["message"] = "Status retrieved"
            result["status"] = status

        elif args.action == 'command':
            if not args.command:
                result["message"] = "Command required"
            else:
                response = pump.run_command(args.command)
                result["success"] = response is not None
                result["message"] = "Command executed"
                result["response"] = response

        elif args.action == 'config':
            if args.param:
                key, value = args.param.split('=', 1)
                try:
                    # Try to convert numeric values
                    if '.' in value:
                        pump.config[key] = float(value)
                    else:
                        pump.config[key] = int(value) if value.isdigit() else value
                    pump.save_config()
                    result["success"] = True
                    result["message"] = f"Set {key} = {value}"
                except:
                    result["message"] = f"Invalid parameter: {args.param}"
            else:
                result["success"] = True
                result["message"] = "Current configuration"
                result["config"] = pump.config

    except Exception as e:
        result["message"] = f"Error: {str(e)}"
        import traceback
        result["error_details"] = traceback.format_exc()

    # Output JSON result
    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()