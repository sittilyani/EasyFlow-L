from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class Dispense(BaseModel):
  port: str # device port
  ml: int # number of milliliters

@app.post('/')
def post_req(req: Dispense):
  # todo: call function to dispense the ml in req.ml to device on port req.port
  # return dict {"success": True} when successful and {"success": False, "error": errorText} as below

  # when successful
  # return {"success": True}
  # when there is an error
  return {"success": False, "error": "error text here"}

if __name__ == "__main__":
  import uvicorn
  uvicorn.run(app, host="0.0.0.0", port=8080)

