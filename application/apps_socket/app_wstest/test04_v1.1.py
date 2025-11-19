import asyncio
import telnetlib3
import websockets

# Configuración del servidor Telnet (AMI de Asterisk)
TELNET_HOST = "75.99.146.30"  # Dirección del servidor Telnet
TELNET_PORT = 7777  # Puerto del servidor Telnet
TELNET_USER = "taxipaterson"  # Usuario Telnet (configurado en Asterisk u otro servicio)
TELNET_PASSWORD = "TaxiPaterson2024!"  # Contraseña

# Lista para almacenar los clientes WebSocket conectados
connected_clients = set()

async def handle_telnet_connection():
    """
    Conecta al servidor Telnet y transmite eventos completos a los clientes WebSocket.
    """
    try:
        print(f"Conectando al servidor Telnet {TELNET_HOST}:{TELNET_PORT}...")

        # Establecer conexión Telnet
        reader, writer = await telnetlib3.open_connection(
            host=TELNET_HOST,
            port=TELNET_PORT,
            connect_minwait=0.1,
            connect_maxwait=5.0,
            force_binary=True,
        )

        # Enviar credenciales de inicio de sesión
        writer.write(f"Action: Login\nUsername: {TELNET_USER}\nSecret: {TELNET_PASSWORD}\n\n")
        await writer.drain()

        # Leer respuesta de bienvenida
        response = await reader.readuntil(b"\r\n")
        print("Respuesta del servidor Telnet:", response.decode())

        if b"Asterisk Call Manager" not in response:
            print("Error al autenticar con el servidor Telnet.")
            writer.close()
            return

        # Suscribirse a eventos
        writer.write("Action: Events\nEventMask: call\nActionID: CALL_ENTRANTE_001\n\n")
        await writer.drain()
        print("✅ Escuchando eventos de Asterisk AMI...\n")

        while True:
            # Leer evento completo (terminado en doble salto de línea)
            event_data = await reader.readuntil(b"\r\n\r\n")
            if event_data.strip():  # Ignora vacíos
                txt = event_data.decode("utf-8").strip()

                # Extraer tipo de evento (opcional)
                lines = txt.splitlines()
                event_type = next((line.split(": ")[1] for line in lines if line.startswith("Event:")), "Desconocido")

                print(f"\n📡 Evento: {event_type}")
                print(txt)
                print("-" * 50)

                # Enviar a todos los clientes WebSocket conectados
                for websocket in connected_clients:
                    await websocket.send(txt)

    except asyncio.CancelledError:
        print("Conexión Telnet cancelada.")
    except Exception as e:
        print("❌ Error en la conexión Telnet:", e)
    finally:
        writer.close()
        print("🔌 Conexión Telnet cerrada.")

async def websocket_handler(websocket, path):
    """
    Maneja las conexiones WebSocket.
    """
    connected_clients.add(websocket)
    print("🟢 Cliente WebSocket conectado. Total:", len(connected_clients))

    try:
        async for message in websocket:
            print("Mensaje recibido del cliente:", message)
            await websocket.send("Echo: " + str(message))
    except websockets.ConnectionClosed as e:
        print("🔴 Cliente desconectado.", e)
    finally:
        connected_clients.remove(websocket)
        print("🟡 Cliente eliminado. Total:", len(connected_clients))

async def main():
    # Iniciar servidor WebSocket
    websocket_server = await websockets.serve(websocket_handler, "0.0.0.0", 8765)
    print("🌐 Servidor WebSocket activo en ws://0.0.0.0:8765")

    # Tarea para conexión Telnet
    telnet_task = asyncio.create_task(handle_telnet_connection())

    # Ejecutar ambos
    await asyncio.gather(
        websocket_server.wait_closed(),
        telnet_task
    )

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("🛑 Servidor detenido por el usuario.")
