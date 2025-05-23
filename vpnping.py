import paramiko
import time
import re
import mysql.connector
from dotenv import load_dotenv
import os
import subprocess
from datetime import datetime

# Load file .env
load_dotenv()

# Ambil konfigurasi dari .env
mikrotik_host = os.getenv("MIKROTIK_HOST")
mikrotik_user = os.getenv("MIKROTIK_USER")
mikrotik_password = os.getenv("MIKROTIK_PASSWORD")

# Konfigurasi database dari .env
db_config = {
    'host': os.getenv("DB_HOST"),
    'user': os.getenv("DB_USER"),
    'password': os.getenv("DB_PASSWORD"),
    'database': os.getenv("DB_DATABASE")
}

def ping_host(host):
    """Fungsi untuk ping host dari lokal untuk cek konektivitas."""
    print(f"[DEBUG] Mencoba ping ke {host}...")
    try:
        ping_command = f"ping -n 4 {host}" if os.name == 'nt' else f"ping -c 4 {host}"
        result = subprocess.run(ping_command, shell=True, capture_output=True, text=True)
        output = result.stdout
        print(f"[DEBUG] Output ping: {output}")

        if result.returncode == 0:
            print(f"[DEBUG] Ping ke {host} berhasil.")
            return True
        else:
            print(f"[DEBUG] Ping ke {host} gagal.")
            return False

    except Exception as e:
        print(f"[DEBUG] Error saat ping {host}: {e}")
        return False

def get_ip_addresses():
    """Ambil semua ip_address dari tabel device_details."""
    try:
        print(f"[DEBUG] Mengambil ip_address dari tabel device_details")
        db = mysql.connector.connect(**db_config)
        cursor = db.cursor()
        query = "SELECT ip_address FROM device_details"
        cursor.execute(query)
        ip_addresses = [row[0] for row in cursor.fetchall()]
        cursor.close()
        db.close()
        print(f"[DEBUG] IP addresses ditemukan: {ip_addresses}")
        return ip_addresses
    except mysql.connector.Error as db_err:
        print(f"[ERROR] Gagal mengambil ip_address dari database: {db_err}")
        return []

def ping_and_store():
    try:
        # Langkah 1: Ping host MikroTik dari lokal
        print(f"[DEBUG] Memulai tes konektivitas ke {mikrotik_host}")

        if not ping_host(mikrotik_host):
            print(f"[ERROR] Tidak bisa ping {mikrotik_host}. Cek jaringan atau firewall.")
            return

        # Langkah 2: Inisialisasi SSH
        print(f"[DEBUG] Menginisialisasi koneksi SSH ke {mikrotik_host}")
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

        # Coba koneksi SSH
        print(f"[DEBUG] Menghubungkan ke {mikrotik_host} dengan user {mikrotik_user}")
        ssh.connect(mikrotik_host, username=mikrotik_user, password=mikrotik_password, timeout=10)
        print(f"[DEBUG] Koneksi SSH berhasil.")

        # Langkah 3: Koneksi ke database
        print(f"[DEBUG] Menghubungkan ke database {db_config['database']} di {db_config['host']}")
        db = mysql.connector.connect(**db_config)
        cursor = db.cursor()
        print(f"[DEBUG] Koneksi database berhasil.")

        # Langkah 4: Ambil IP dari device_details
        target_ips = get_ip_addresses()
        if not target_ips:
            print(f"[ERROR] Tidak ada IP address yang ditemukan di tabel device_details.")
            ssh.close()
            cursor.close()
            db.close()
            return

        # Langkah 5: Loop untuk setiap IP
        for ip in target_ips:
            print(f"[DEBUG] Menjalankan ping ke {ip} dari MikroTik")
            command = f"ping {ip} count=4"
            stdin, stdout, stderr = ssh.exec_command(command)
            output = stdout.read().decode()
            error_output = stderr.read().decode()

            # Debug output perintah
            print(f"[DEBUG] Output ping ke {ip}: {output}")

            if error_output:
                print(f"[DEBUG] Error output: {error_output}")

            # Cek jumlah paket yang diterima menggunakan regex
            received_match = re.search(r"received=(\d+)", output)
            packet_loss_match = re.search(r"packet-loss=(\d+)%", output)
            ping_stats_match = re.search(r"sent=\d+ received=\d+ packet-loss=\d+% min-rtt=\d+ms avg-rtt=\d+ms max-rtt=\d+ms", output)

            # Inisialisasi nilai default
            status = 0
            packet_loss = 100
            message = "Ping Result: No response"

            if received_match:
                received_count = int(received_match.group(1))
                status = 1 if received_count > 2 else 0
                print(f"[DEBUG] Jumlah paket diterima untuk {ip}: {received_count}")
            else:
                print(f"[DEBUG] Tidak menemukan 'received=X' di output untuk {ip}")

            if packet_loss_match:
                packet_loss = int(packet_loss_match.group(1))
                print(f"[DEBUG] Packet loss untuk {ip}: {packet_loss}%")
            else:
                print(f"[DEBUG] Tidak menemukan 'packet-loss=X%' di output untuk {ip}")

            if ping_stats_match:
                sent = ping_stats_match.group(1)
                received = ping_stats_match.group(2)
                packet_loss_stat = ping_stats_match.group(3)
                min_rtt = ping_stats_match.group(4)
                avg_rtt = ping_stats_match.group(5)
                max_rtt = ping_stats_match.group(6)
                timestamp = datetime.now().strftime("%d %b %Y %H:%M:%S")
                message = f"[{timestamp}] Ping Stats: sent={sent} | received={received} | packet-loss={packet_loss_stat}% | min-rtt={min_rtt}ms | avg-rtt={avg_rtt}ms | max-rtt={max_rtt}ms"
                print(f"[DEBUG] Message untuk {ip}: {message}")
            else:
                timestamp = datetime.now().strftime("%d %b %Y %H:%M:%S")
                message = f"[{timestamp}] Ping Stats: No response"
                print(f"[DEBUG] Tidak menemukan statistik ping di output untuk {ip}")

            print(f"[DEBUG] Status ping ke {ip}: {'Success' if status == 1 else 'Failed'}")

            # Simpan ke database
            query = "INSERT INTO ping_results (ip_address, status, packet_loss, message, created_at, updated_at) VALUES (%s, %s, %s, %s, NOW(), NOW())"
            cursor.execute(query, (ip, status, packet_loss, message))
            print(f"[DEBUG] Data untuk {ip} tersimpan ke database.")

        # Commit dan tutup koneksi
        print(f"[DEBUG] Melakukan commit dan menutup koneksi.")
        db.commit()
        cursor.close()
        db.close()
        ssh.close()
        print(f"[DEBUG] Koneksi SSH dan database ditutup.")

    except paramiko.AuthenticationException as auth_err:
        print(f"[ERROR] Gagal autentikasi SSH: {auth_err}")
    except paramiko.SSHException as ssh_err:
        print(f"[ERROR] Gagal koneksi SSH: {ssh_err}")
    except mysql.connector.Error as db_err:
        print(f"[ERROR] Gagal koneksi database: {db_err}")
    except Exception as e:
        print(f"[ERROR] Error umum: {e}")

# Jalankan sekali untuk tes
ping_and_store()

# Untuk deploy ke Linux, uncomment baris berikut:
# while True:
#     ping_and_store()
#     time.sleep(300)  # 300 detik = 5 menit
