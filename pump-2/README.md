# New pump calling python script

## Steps to run the script

1. Initiatialize the virtual environment
    ```bash
    python -m venv .venv
    ```
2. Activate the virtual environment
    ```bash
    source .venv/bin/activate
    ```
3. Install dependencies
    ```bash
    pip install -r requirements.txt
    ```
4. Run the script
    ```bash
    python main.py
    ```

### Preferrably you can use `uv` python to manage dependencies and run the script.

1. Install `uv` globally by following the official installation guide at[official uv](https://docs.astral.sh/uv/getting-started/installation/#installation-methods)
2. Create virtual environment
    ```bash
    uv venv .venv
    ```
3. Activate virtual environment
    ```bash
    source .venv/bin/activate
    ```
4. Install dependencies
    ```bash
    uv add -r requirements.txt
    ```
5. Run the script
    ```bash
    uv run main.py
    ```

## Packaging the script

```bash
pyinstaller --onefile main.py
```
