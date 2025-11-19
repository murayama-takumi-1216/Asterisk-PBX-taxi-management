import asyncio
import telnetlib3
import websockets
import json

# Configuración de AMI
TELNET_HOST = "75.99.146.30"  # Dirección del servidor Telnet
TELNET_PORT = 7777  # Puerto del servidor Telnet
TELNET_USER = "taxipaterson"  # Usuario Telnet (configurado en Asterisk u otro servicio)
TELNET_PASSWORD = "TaxiPaterson2024!"  # Contraseña

# WebSocket
connected_clients = set()


def parse_event_to_dict(event_text: str) -> dict:
    """
    Convierte el bloque de texto del evento AMI a un diccionario.
    """
    event_dict = {}
    for line in event_text.splitlines():
        if ": " in line:
            key, value = line.split(": ", 1)
            event_dict[key.strip()] = value.strip()
    return event_dict


async def handle_telnet_connection():
    """
    Conecta a Asterisk AMI vía Telnet y transmite eventos como JSON.
    """
    try:
        print(f"Conectando a {TELNET_HOST}:{TELNET_PORT}...")

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
        print("Respuesta:", response.decode())

        if b"Asterisk Call Manager" not in response:
            print("❌ Error de autenticación.")
            writer.close()
            return

        # Suscribirse a eventos
        writer.write("Action: Events\nEventMask: call\nActionID: WS_MONITOR\n\n")
        await writer.drain()
        print("✅ Escuchando eventos de llamadas...\n")

        while True:
            event_data = await reader.readuntil(b"\r\n\r\n")
            if event_data.strip():
                event_text = event_data.decode("utf-8").strip()
                event_dict = parse_event_to_dict(event_text)

                if "Event" in event_dict:
                    json_event = json.dumps(event_dict, indent=2)
                    print(f"\n📡 Evento JSON: {event_dict['Event']}")
                    print(json_event)

                    for websocket in connected_clients:
                        await websocket.send(json_event)

    except asyncio.CancelledError:
        print("⚠️ Conexión cancelada.")
    except Exception as e:
        print("❌ Error:", e)
    finally:
        writer.close()
        print("🔌 Conexión cerrada.")


async def websocket_handler(websocket, path):
    connected_clients.add(websocket)
    print("🟢 Cliente WebSocket conectado. Total:", len(connected_clients))
    try:
        async for message in websocket:
            print("Mensaje del cliente:", message)
            await websocket.send("Echo: " + str(message))
    except websockets.ConnectionClosed as e:
        print("🔴 Cliente desconectado.", e)
    finally:
        connected_clients.remove(websocket)
        print("🟡 Total conectados:", len(connected_clients))


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
        print("🛑 Servidor detenido.")
