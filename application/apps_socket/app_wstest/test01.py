import asyncio
import websockets
import telnetlib3
import json
from typing import Dict, Set, Union
from dataclasses import dataclass
from datetime import datetime


@dataclass
class Client:
    id: str
    # connection: Union[websockets.WebSocketServerProtocol, telnetlib3.TelnetReader]
    connection: Union[websockets.ServerProtocol, telnetlib3.TelnetReader]
    type: str  # 'websocket' or 'telnet'
    connected_at: datetime


class CommunicationServer:
    # def __init__(self, websocket_port: int = 8765, telnet_port: int = 23):
    def __init__(self, websocket_port: int = 8765, telnet_port: int = 5038):
        self.websocket_port = websocket_port
        self.telnet_port = telnet_port
        self.clients: Dict[str, Client] = {}
        self.rooms: Dict[str, Set[str]] = {}

    async def start(self):
        # Iniciar ambos servidores
        websocket_server = websockets.serve(
            self.handle_websocket,
            "localhost",
            self.websocket_port
        )
        telnet_server = telnetlib3.create_server(
            host='localhost',
            port=self.telnet_port,
            connect_maxwait=10.0,
            handler=self.handle_telnet
        )

        # Ejecutar ambos servidores
        await asyncio.gather(
            websocket_server,
            telnet_server
        )

        print(f"Servidor WebSocket ejecutándose en ws://localhost:{self.websocket_port}")
        print(f"Servidor Telnet ejecutándose en localhost:{self.telnet_port}")

    async def broadcast(self, message: str, exclude_client: str = None):
        """Envía un mensaje a todos los clientes conectados"""
        for client_id, client in self.clients.items():
            if client_id != exclude_client:
                try:
                    if client.type == 'websocket':
                        await client.connection.send(message)
                    else:  # telnet
                        client.connection.write(message + '\r\n')
                except Exception as e:
                    print(f"Error al enviar mensaje a {client_id}: {e}")

    # async def handle_websocket(self, websocket: websockets.WebSocketServerProtocol, path: str):
    async def handle_websocket(self, websocket: websockets.ServerProtocol, path: str):
        """Maneja conexiones WebSocket"""
        client_id = f"ws_{id(websocket)}"
        client = Client(
            id=client_id,
            connection=websocket,
            type='websocket',
            connected_at=datetime.now()
        )
        self.clients[client_id] = client

        try:
            await self.broadcast(f"Cliente WebSocket {client_id} conectado")

            async for message in websocket:
                try:
                    # Procesar mensaje como JSON
                    data = json.loads(message)
                    response = await self.process_command(data, client_id)
                    await websocket.send(json.dumps(response))
                except json.JSONDecodeError:
                    # Si no es JSON, tratar como mensaje de texto simple
                    await self.broadcast(f"{client_id}: {message}", client_id)

        except websockets.exceptions.ConnectionClosed:
            pass
        finally:
            del self.clients[client_id]
            await self.broadcast(f"Cliente WebSocket {client_id} desconectado")

    async def handle_telnet(self, reader: telnetlib3.TelnetReader, writer: telnetlib3.TelnetWriter):
        """Maneja conexiones Telnet"""
        client_id = f"telnet_{id(reader)}"
        client = Client(
            id=client_id,
            connection=writer,
            type='telnet',
            connected_at=datetime.now()
        )
        self.clients[client_id] = client

        try:
            writer.write(f"Bienvenido al servidor. Tu ID es {client_id}\r\n")
            await self.broadcast(f"Cliente Telnet {client_id} conectado")

            while True:
                message = await reader.readline()
                if not message:
                    break

                message = message.strip()
                if message.startswith('/'):
                    # Procesar como comando
                    try:
                        command = json.loads(message[1:])
                        response = await self.process_command(command, client_id)
                        writer.write(json.dumps(response) + '\r\n')
                    except json.JSONDecodeError:
                        writer.write("Error: Comando inválido\r\n")
                else:
                    # Broadcast mensaje normal
                    await self.broadcast(f"{client_id}: {message}", client_id)

        except Exception as e:
            print(f"Error en cliente Telnet {client_id}: {e}")
        finally:
            del self.clients[client_id]
            writer.close()
            await self.broadcast(f"Cliente Telnet {client_id} desconectado")

    async def process_command(self, data: dict, client_id: str) -> dict:
        """Procesa comandos de los clientes"""
        command = data.get('command', '').lower()

        if command == 'list':
            return {
                'status': 'ok',
                'clients': [
                    {
                        'id': c.id,
                        'type': c.type,
                        'connected_at': c.connected_at.isoformat()
                    }
                    for c in self.clients.values()
                ]
            }

        elif command == 'join':
            room = data.get('room')
            if not room:
                return {'status': 'error', 'message': 'Room name required'}

            if room not in self.rooms:
                self.rooms[room] = set()
            self.rooms[room].add(client_id)
            return {'status': 'ok', 'message': f'Joined room {room}'}

        elif command == 'leave':
            room = data.get('room')
            if room in self.rooms and client_id in self.rooms[room]:
                self.rooms[room].remove(client_id)
                return {'status': 'ok', 'message': f'Left room {room}'}
            return {'status': 'error', 'message': 'Not in room'}

        return {'status': 'error', 'message': 'Unknown command'}


async def main():
    server = CommunicationServer()
    await server.start()


if __name__ == "__main__":
    asyncio.run(main())

"""
# Ejemplo de cliente WebSocket (Python)
import asyncio
import websockets
import json

async def websocket_client():
    uri = "ws://localhost:8765"
    async with websockets.connect(uri) as websocket:
        # Enviar comando para listar clientes
        await websocket.send(json.dumps({
            'command': 'list'
        }))
        response = await websocket.recv()
        print(f"Clientes conectados: {response}")

        # Enviar mensaje normal
        await websocket.send("¡Hola desde el cliente WebSocket!")

        # Esperar respuestas
        while True:
            response = await websocket.recv()
            print(f"Recibido: {response}")

# Para conectar por Telnet:
# telnet localhost 23
# Luego puedes enviar comandos como:
# /{"command": "list"}
# O mensajes normales escribiendo directamente
"""
