#!/usr/bin/env python3
"""
Emergency test script for Cole-Parmer Masterflex pump
Run this directly to test if pump motor runs
"""

import serial
import time
import sys

def emergency_test():
    print("=" * 60)
    print("EMERGENCY PUMP TEST - Masterflex L/S Series")
    print("=" * 60)
    print()

    port = input("Enter COM port (default COM20): ").strip()
    if not port:
        port = 'COM20'

    print(f"\nTesting {port} at 9600 baud...")

    try:
        # Open serial port
        ser = serial.Serial(
            port=port,
            baudrate=9600,
            bytesize=8,
            parity='N',
            stopbits=1,
            timeout=2
        )

        print("? Port opened successfully")
        time.sleep(0.5)

        # Test sequence
        commands = [
            (b'REM\r', "Enter remote mode"),
            (b'CLR\r', "Clear errors"),
            (b'DIA 14.7\r', "Set tubing diameter"),
            (b'VOL 0\r', "Reset volume"),
            (b'DIR INF\r', "Set direction to infuse"),
            (b'RAT 20\r', "Set rate to 20 ml/min"),
        ]

        print("\n--- Initialization ---")
        for cmd, desc in commands:
            print(f"{desc}...", end=' ')
            ser.write(cmd)
            ser.flush()
            time.sleep(0.3)

            # Check for response
            if ser.in_waiting:
                resp = ser.read(ser.in_waiting)
                print(f"Response: {resp}")
            else:
                print("OK")

        # START PUMP
        print("\n" + "=" * 40)
        print("STARTING PUMP MOTOR...")
        print("=" * 40)

        ser.write(b'RUN\r')
        ser.flush()
        time.sleep(0.5)

        print("\n? PUMP SHOULD BE RUNNING NOW!")
        print("Listen for motor sound...")
        print("Watch for fluid movement...")
        print("\nCheck:")
        print("1. Is the pump head rotating?")
        print("2. Is fluid moving through tubing?")
        print("3. Any error lights on pump display?")

        input("\nPress Enter to STOP pump...")

        # STOP PUMP
        print("\nStopping pump...")
        ser.write(b'STOP\r')
        ser.flush()
        time.sleep(0.5)

        ser.write(b'RAT 0\r')
        ser.flush()

        ser.close()

        print("\n" + "=" * 40)
        print("TEST COMPLETE")
        print("=" * 40)

        result = input("\nDid the pump motor run? (y/n): ").lower()
        if result == 'y':
            print("\n? SUCCESS: Pump is working!")
            print("The issue is in command sequence or timing.")
        else:
            print("\n? FAILURE: Pump did not run.")
            print("\nTroubleshooting steps:")
            print("1. Check pump power")
            print("2. Verify COM port")
            print("3. Check tubing installation")
            print("4. Try different baud rate")
            print("5. Check pump is not in LOCAL mode")

    except Exception as e:
        print(f"\n? ERROR: {e}")
        print("\nTroubleshooting:")
        print(f"1. Is {port} the correct COM port?")
        print("2. Is the pump powered ON?")
        print("3. Is the USB cable connected?")
        print("4. Are drivers installed?")

    input("\nPress Enter to exit...")

if __name__ == "__main__":
    emergency_test()