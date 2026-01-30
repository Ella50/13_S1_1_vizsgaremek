import serial
import serial.tools.list_ports
import threading
from datetime import datetime, timezone
from flask import Flask, request, jsonify
import time

BAUD = 9600
app = Flask(__name__)

latest = {"uid": None, "scanned_at": None}
lock = threading.Lock()

def now_iso():
    return datetime.now(timezone.utc).isoformat()

def find_port():
    ports = list(serial.tools.list_ports.comports())
    # DEBUG: írd ki mit lát
    # print("DEBUG: ports:", [p.device + " " + (p.description or "") for p in ports])

    for p in ports:
        desc = (p.description or "").lower()
        if "arduino" in desc or "ch340" in desc or "usb serial" in desc:
            return p.device

    return ports[0].device if ports else None

def serial_reader():
    while True:
        port = find_port()
        print("DEBUG: found port:", port)

        if not port:
            time.sleep(2)
            continue

        try:
            print("DEBUG: opening port:", port)
            ser = serial.Serial(port, BAUD, timeout=1)
            print("DEBUG: port opened OK:", port)

            while True:
                line = ser.readline().decode(errors="ignore").strip()
                if not line:
                    continue

                print("DEBUG: UID read:", line)

                with lock:
                    latest["uid"] = line
                    latest["scanned_at"] = now_iso()

                with open("rfid.txt", "a", encoding="utf-8") as f:
                    f.write(line + "\n")

        except Exception as e:
            print("DEBUG: serial error:", repr(e))
            time.sleep(1)

@app.get("/latest-scan")
def latest_scan():
    since = request.args.get("since")

    with lock:
        uid = latest["uid"]
        scanned_at = latest["scanned_at"]

    if not uid or not scanned_at:
        return jsonify({"success": True, "data": None})

    if since:
        try:
            since_dt = datetime.fromisoformat(since.replace("Z", "+00:00"))
            scanned_dt = datetime.fromisoformat(scanned_at.replace("Z", "+00:00"))
            if scanned_dt <= since_dt:
                return jsonify({"success": True, "data": None})
        except Exception:
            pass

    return jsonify({"success": True, "data": {"uid": uid, "scanned_at": scanned_at}})

if __name__ == "__main__":
    threading.Thread(target=serial_reader, daemon=True).start()
    app.run(host="127.0.0.1", port=5001, debug=False)
