import asyncio
import telnetlib
import websockets

# Configuración del servidor Telnet
TELNET_HOST = "127.0.0.1"  # Dirección del servidor Telnet
TELNET_PORT = 5038         # Puerto del servidor Telnet
TELNET_USER = "admin"    # Usuario Telnet (configurado en Asterisk u otro servicio)
TELNET_PASSWORD = "secret"  # Contraseña

# Lista para almacenar los clientes WebSocket conectados
connected_clients = set()

async def handle_telnet_connection():
    """
    Conecta al servidor Telnet y transmite datos recibidos a los clientes WebSocket.
    """
    try:
        print(f"Conectando al servidor Telnet {TELNET_HOST}:{TELNET_PORT}...")
        tn = telnetlib.Telnet(TELNET_HOST, TELNET_PORT)

        # Enviar credenciales de inicio de sesión
        tn.write(f"Action: Login\nUsername: {TELNET_USER}\nSecret: {TELNET_PASSWORD}\n\n".encode())

        # Leer respuesta de inicio de sesión
        # response = tn.read_until(b"\n\n").decode()
        response = tn.read_until(b"\n\n", 1).decode()
        print(f"Respuesta del servidor Telnet: {response}")

        if "Success" not in response:
            print("Error al autenticar con el servidor Telnet.")
            tn.close()
            return

        # Suscribirse a eventos
        # tn.write(b"Action: Events\nEventMask: on\n\n")
        tn.write(b"Action: Events\nEventMask: call\n\n")
        print("Escuchando eventos de Telnet...")

        while True:
            # Leer datos del servidor Telnet
            # event_data = tn.read_until(b"\n\n").decode()
            event_data = tn.read_until(b"\n\n", 3).decode()
            if event_data.strip():  # Ignorar líneas vacías
                print(f"Evento recibido: {event_data}")

                # Enviar datos a todos los clientes WebSocket conectados
                for websocket in connected_clients:
                    await websocket.send(event_data)

    except Exception as e:
        print(f"Error en la conexión Telnet: {e}")
    finally:
        tn.close()
        print("Conexión Telnet cerrada.")

async def websocket_handler(websocket, path):
    """
    Maneja las conexiones WebSocket.
    """
    # Agregar cliente a la lista de conectados
    connected_clients.add(websocket)
    print(f"Nuevo cliente conectado. Total clientes: {len(connected_clients)}")

    try:
        async for message in websocket:
            # Recibir mensajes desde el cliente (opcional)
            print(f"Mensaje recibido del cliente: {message}")
            await websocket.send(f"Echo: {message}")
    except websockets.ConnectionClosed:
        print("Cliente desconectado.")
    finally:
        # Eliminar cliente de la lista
        connected_clients.remove(websocket)
        print(f"Cliente desconectado. Total clientes: {len(connected_clients)}")

async def main():
    # Iniciar el servidor WebSocket
    websocket_server = await websockets.serve(websocket_handler, "0.0.0.0", 8765)
    print("Servidor WebSocket iniciado en ws://0.0.0.0:8765")

    # Iniciar el bucle de conexión Telnet
    await handle_telnet_connection()

    # Mantener el servidor WebSocket en ejecución
    await websocket_server.wait_closed()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("Servidor detenido.")
