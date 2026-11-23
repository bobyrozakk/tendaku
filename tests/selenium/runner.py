import subprocess
import sys
import time
import urllib.request
from pathlib import Path

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]

print("Starting Laravel server on http://localhost:8000...")
proc = subprocess.Popen(
    ["php", "artisan", "serve", "--port=8000"],
    cwd=ROOT,
    stdout=subprocess.DEVNULL,
    stderr=subprocess.DEVNULL,
)

try:
    ready = False
    for i in range(30):
        time.sleep(1)
        try:
            with urllib.request.urlopen("http://localhost:8000", timeout=2) as resp:
                if resp.status == 200:
                    ready = True
                    break
        except Exception:
            pass

    if not ready:
        print("ERROR: Server failed to start on http://localhost:8000", file=sys.stderr)
        sys.exit(1)

    print("Server ready! Running Selenium E2E tests...")
    pytest_bin = HERE / ".venv" / "Scripts" / "pytest.exe"
    args = sys.argv[1:] if len(sys.argv) > 1 else ["-v"]
    res = subprocess.run([str(pytest_bin), *args], cwd=HERE)
    sys.exit(res.returncode)
finally:
    print("Stopping Laravel server...")
    proc.terminate()
    try:
        proc.wait(timeout=5)
    except subprocess.TimeoutExpired:
        proc.kill()
