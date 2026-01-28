import serial

ser = serial.Serial('COM4', 9600)
with open("rfid.txt", "w") as f:
    while True:
        line = ser.readline().decode().strip()
        f.write(line + "\n")
        f.flush()
