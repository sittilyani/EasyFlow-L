#!/usr/bin/env python3
"""
Direct command-line test for pump
"""

import serial
import time

# Simple test
ser = serial.Serial('COM20', 9600, timeout=2)
print(f"Port: {ser.port}, Baud: {ser.baudrate}")

# Send individual commands
test_commands = [
    "REM",
    "?",
    "RAT 10",
    "RUN",
    "STOP",
    "RAT?"
]

for cmd in test_commands:
    print(f"\nSending: {cmd}")
    ser.write(f"{cmd}\r".encode())
    time.sleep(0.5)

    if ser.in_waiting:
        resp = ser.read(ser.in_waiting)
        print(f"Response: {resp}")
    else:
        print("No response")

ser.close()