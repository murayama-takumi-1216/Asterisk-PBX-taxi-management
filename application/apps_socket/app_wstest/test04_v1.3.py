import asyncio
import telnetlib3
import websockets
import json

# Configuración de conexión AMI
TELNET_HOST = "75.99.146.30"  # Dirección del servidor Telnet
TELNET_PORT = 7777  # Puerto del servidor Telnet
TELNET_USER = "taxipaterson"  # Usuario Telnet (configurado en Asterisk u otro servicio)
TELNET_PASSWORD = "TaxiPaterson2024!"  # Contraseña

# Extensiones a monitorear
EXTENSIONES_MONITOREADAS = ['1001', '1002']

# WebSocket
connected_clients = set()

def parse_event_to_dict(event_text: str) -> dict:
    """
    Convierte el texto del evento AMI a diccionario.
    """
    event_dict = {}
    for line in event_text.splitlines():
        if ": " in line:
            key, value = line.split(": ", 1)
            event_dict[key.strip()] = value.strip()
    return event_dict

def identificar_tipo_llamada(event: dict) -> str:
    """
    Clasifica el tipo de evento: entrante, contestada o finalizada.
    """
    tipo_evento = event.get("Event")
    context = event.get("Context", "")
    if tipo_evento == "Newchannel" and context.startswith("from"):
        return "Llamada entrante"
    elif tipo_evento == "BridgeEnter":
        return "Llamada contestada"
    elif tipo_evento == "Hangup":
        return "Llamada finalizada"
    return None

def extension_involucrada(event: dict) -> str:
    """
    Intenta obtener la extensión monitoreada involucrada en el evento.
    """
    for key in ["CallerIDNum", "ConnectedLineNum", "Exten"]:
        ext = event.get(key, "")
        if ext in EXTENSIONES_MONITOREADAS:
            return ext
    # Extraer de Channel: ej. SIP/1001-000001
    canal = event.get("Channel", "")
    if "/" in canal:
        posible_ext = canal.split("/")[1].split("-")[0]
        if posible_ext in EXTENSIONES_MONITOREADAS:
            return posible_ext
    return None

async def handle_telnet_connection():
    """
    Conecta al AMI, escucha eventos y envía eventos filtrados como JSON por WebSocket.
    """
    try:
        print(f"Conectando al AMI {TELNET_HOST}:{TELNET_PORT}...")

        reader, writer = await telnetlib3.open_connection(
            host=TELNET_HOST,
            port=TELNET_PORT,
            connect_minwait=0.1,
            connect_maxwait=5.0,
            force_binary=True,
        )

        # Login
        writer.write(f"Action: Login\nUsername: {TELNET_USER}\nSecret: {TELNET_PASSWORD}\n\n")
        await writer.drain()
        response = await reader.readuntil(b"\r\n")
        print("Respuesta del servidor AMI:", response.decode())

        if b"Asterisk Call Manager" not in response:
            print("❌ Error de autenticación.")
            writer.close()
            return

        # Suscripción a eventos
        writer.write("Action: Events\nEventMask: call\nActionID: WS_MONITOR\n\n")
        await writer.drain()
        print("✅ Monitoreo de eventos activado...\n")

        while True:
            # Leer evento completo
            event_data = await reader.readuntil(b"\r\n\r\n")
            if event_data.strip():
                raw_text = event_data.decode("utf-8").strip()
                event_dict = parse_event_to_dict(raw_text)

                if "Event" in event_dict:
                    ext = extension_involucrada(event_dict)
                    tipo = identificar_tipo_llamada(event_dict)

                    if ext and tipo:
                        json_obj = {
                            "tipo": tipo,
                            "extension": ext,
                            "evento": event_dict
                        }
                        json_text = json.dumps(json_obj, indent=2)
                        print(f"\n📞 [{tipo}] Extensión: {ext}")
                        print(json_text)

                        # Enviar a clientes WebSocket
                        for websocket in connected_clients:
                            await websocket.send(json_text)

    except asyncio.CancelledError:
        print("⚠️ Conexión cancelada.")
    except Exception as e:
        print("❌ Error:", e)
    finally:
        writer.close()
        print("🔌 Conexión AMI cerrada.")

async def websocket_handler(websocket, path):
    connected_clients.add(websocket)
    print("🟢 Cliente WebSocket conectado. Total:", len(connected_clients))
    try:
        async for message in websocket:
            print("Mensaje recibido:", message)
            await websocket.send("Echo: " + str(message))
    except websockets.ConnectionClosed as e:
        print("🔴 Cliente desconectado.", e)
    finally:
        connected_clients.remove(websocket)
        print("🟡 Cliente eliminado. Total:", len(connected_clients))

async def main():
    websocket_server = await websockets.serve(websocket_handler, "0.0.0.0", 8765)
    print("🌐 WebSocket en ws://0.0.0.0:8765")

    telnet_task = asyncio.create_task(handle_telnet_connection())

    await asyncio.gather(
        websocket_server.wait_closed(),
        telnet_task
    )

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("🛑 Servidor detenido por el usuario.")
