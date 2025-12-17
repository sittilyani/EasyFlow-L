#!/usr/bin/env python3
import serial
import time
import sys

if len(sys.argv) > 1:
    command = sys.argv[1]

    try:
        ser = serial.Serial(
            port='COM20',
            baudrate=9600,
            bytesize=8,
            parity='N',
            stopbits=1,
            timeout=2
        )

        # Send command with CR
        ser.write((command + '\r').encode('ascii'))
        ser.flush()

        # Read response
        time.sleep(0.5)
        response = b""
        while ser.in_waiting:
            response += ser.read(1)

        if response:
            print(response.decode('ascii', errors='ignore').strip())
        else:
            print("OK")

        ser.close()

    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)