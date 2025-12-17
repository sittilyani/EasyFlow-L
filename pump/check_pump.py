import serial
import time
from datetime import datetime

def diagnose_pump(port='COM20', baudrate=19200):
    print(f"=== Pump Diagnostic Tool ===")
    print(f"Port: {port}, Baud: {baudrate}")
    print(f"Time: {datetime.now()}")
    print()

    # List available ports
    print("Available COM ports:")
    import serial.tools.list_ports
    ports = serial.tools.list_ports.comports()
    for p in ports:
        print(f"  - {p.device}: {p.description}")

    print()
    print("Testing connection...")

    try:
        # Try different baud rates
        baud_rates = [19200, 9600, 38400, 57600, 115200, 4800]

        for baud in baud_rates:
            print(f"\nTrying {baud} baud...")
            try:
                ser = serial.Serial(
                    port=port,
                    baudrate=baud,
                    timeout=2,
                    write_timeout=2
                )

                # Send test commands
                test_commands = [
                    b'RDY?\r\n',
                    b'*IDN?\r\n',
                    b'TEST\r\n',
                    b'\r\n'  # Just carriage return
                ]

                for cmd in test_commands:
                    print(f"Sending: {cmd}")
                    ser.write(cmd)
                    time.sleep(0.5)

                    # Read response
                    if ser.in_waiting:
                        response = ser.read(ser.in_waiting)
                        print(f"Response: {response}")
                        if response:
                            print(f"Decoded: {response.decode('ascii', errors='ignore')}")
                    else:
                        print("No response")

                ser.close()

            except Exception as e:
                print(f"Failed at {baud} baud: {e}")
                continue

    except Exception as e:
        print(f"Diagnostic failed: {e}")

if __name__ == "__main__":
    diagnose_pump('COM20')