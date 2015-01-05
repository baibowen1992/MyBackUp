#coding:utf-8
from flask import Flask
from flask.ext.restful import request, abort, Api, Resource
import json

app = Flask(__name__)
api = Api(app)

# A login API
#   post a json data like {"username":"admin","password":"123456"}
#   if the request valus is admin and 123456,return OK,or return error

class Login(Resource):
    def get(self):
        return "error support", 404

    def post(self):
        logindata=json.dumps(request.get_json())
        print request.json["username"]
        if request.json["username"] == 'admin' or request.json["username"] == 'test' or request.json["username"] == 'test1':
            if request.json["token"]== '123456':
                userinfo={"name":"gci","id":0,"state":0,"comment":"","token":"token","account":"gci","email":"gci@gci.com",\
                  "roleId":0,"phone":"010-88888888","mobile":"19888888888"}
                result={"code":0,"msg":"ok","data":userinfo}
                return result, 202
            else:
                result={"code":1,"msg":"wrong user/password","data":"null"}
                return result, 404
        else:
            result={"code":1,"msg":"wrong user/password","data":"null"}
            return result, 404
			
class gets3keys(Resource):
    def get(self):
        return "error support", 404

    def post(self):
        logindata=json.dumps(request.get_json())
        print request.json["resourcePool"]
        if request.json["token"] == '123456' :
            if request.json["resourcePool"]== 'huhehaote':
                keyinfo=[{"username":"admin","status":"active","password":"admin","secretkey":"awocloudtest ","used":"16","allocated":0,"accesskey":"swocloudtest "}]
                result={"code":0,"msg":"Welcome","data":keyinfo}
                return result, 202
            else:
                result={"code":1,"msg":"wrong user/password","data":"null"}
                return result, 404
        else:
            result={"code":1,"msg":"wrong user/password","data":"null"}
            return result, 404

api.add_resource(Login, '/portal/user/checkIpAndIo')
api.add_resource(gets3keys, '/portal/instance/obs/getObsUser')

if __name__ == '__main__':
    app.run(debug=True,host='0.0.0.0',port=8082)

