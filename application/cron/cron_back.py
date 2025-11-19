import urllib.request

url_base = "http://147.185.238.245:8000/webhook/ami-back/llamada-cliente-back/"
req = urllib.request.Request(url_base)
with urllib.request.urlopen(req) as response:
    response.read()
    print(response.status)
    # data = response.read()
    # print(response)
