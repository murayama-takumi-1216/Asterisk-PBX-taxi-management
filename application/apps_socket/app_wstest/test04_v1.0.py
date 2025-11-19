import asyncio
import telnetlib3
import websockets

# Configuración del servidor Telnet
# TELNET_HOST = "127.0.0.1"  # Dirección del servidor Telnet
# TELNET_PORT = 5038  # Puerto del servidor Telnet
# TELNET_USER = "admin"  # Usuario Telnet (configurado en Asterisk u otro servicio)
# TELNET_PASSWORD = "secret"  # Contraseña
TELNET_HOST = "75.99.146.30"  # Dirección del servidor Telnet
TELNET_PORT = 7777  # Puerto del servidor Telnet
TELNET_USER = "taxipaterson"  # Usuario Telnet (configurado en Asterisk u otro servicio)
TELNET_PASSWORD = "TaxiPaterson2024!"  # Contraseña

# Lista para almacenar los clientes WebSocket conectados
connected_clients = set()


async def handle_telnet_connection():
    """
    Conecta al servidor Telnet y transmite datos recibidos a los clientes WebSocket.
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

        # Leer respuesta de inicio de sesión
        # response = await reader.readuntil("\n\n")
        response = await reader.readuntil(b"\r\n")
        print("Respuesta del servidor Telnet: ", response)

        # if "Success" not in response:
        if b"Asterisk Call Manager" not in response:
            print("Error al autenticar con el servidor Telnet.")
            writer.close()
            return

        # Suscribirse a eventos
        writer.write("Action: Events\nEventMask: call\nActionID: CALL_ENTRANTE_001\n\n")
        await writer.drain()
        print("Escuchando eventos de Telnet...")
        print("--------------------------------------------------------->")

        while True:
            # print("\n\n")
            # Leer datos del servidor Telnet
            # event_data = await reader.readuntil("\n\n")
            event_data = await reader.readuntil(b"\r\n")
            if event_data.strip():  # Ignorar líneas vacías
                txt = event_data.decode('utf-8')
                if isinstance(txt, str):
                    print("Evento recibido: ", txt.strip())

                # Enviar datos a todos los clientes WebSocket conectados
                for websocket in connected_clients:
                    # await websocket.send(event_data)
                    await websocket.send(str(event_data))

    except asyncio.CancelledError:
        print("Conexión Telnet cancelada.")
    except Exception as e:
        print("Error en la conexión Telnet: ", e)
    finally:
        writer.close()
        print("Conexión Telnet cerrada.")


async def websocket_handler(websocket, path):
    """
    Maneja las conexiones WebSocket.
    """
    # Agregar cliente a la lista de conectados
    connected_clients.add(websocket)
    print("Nuevo cliente conectado. Total clientes: ", len(connected_clients))

    try:
        async for message in websocket:
            # Manejar mensajes desde el cliente (opcional)
            print("Mensaje recibido del cliente: ", message)
            await websocket.send("Echo: ", str(message))
    except websockets.ConnectionClosed as e:
        print("Cliente desconectado.", e)
    finally:
        # Eliminar cliente de la lista
        connected_clients.remove(websocket)
        print("Cliente desconectado. Total clientes: ", len(connected_clients))


async def main():
    # Iniciar el servidor WebSocket
    websocket_server = await websockets.serve(websocket_handler, "0.0.0.0", 8765)
    print("Servidor WebSocket iniciado en ws://0.0.0.0:8765")

    # Iniciar el bucle de conexión Telnet
    telnet_task = asyncio.create_task(handle_telnet_connection())

    # Mantener ambos servidores en ejecución
    await asyncio.gather(
        websocket_server.wait_closed(),
        telnet_task
    )


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("Servidor detenido.")
