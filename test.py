import serial

ser = serial.Serial('COM4', 9600)

while True:
    line = ser.readline().decode().strip()
