import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_constants.dart';

class ApiService {
  // 1. Servicio de Login
  Future<Map<String, dynamic>> login(String empresaId, String usuario, String password) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.login),
        body: {
          'empresa_id': empresaId,
          'username': usuario,
          'password': password,
        },
      );
      return _processResponse(response);
    } catch (e) {
      return {'status': 'error', 'message': 'Error de conexión'};
    }
  }

  // 2. Servicio de Registro (Con tus campos específicos)
  Future<Map<String, dynamic>> register({
    required String nombre,
    required String apellidos,
    required String codigoEmpresa,
    required String pin, // 6 dígitos
    required String password,
  }) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.register),
        headers: {'Accept': 'application/json'},
        body: {
          'name': nombre,
          'last_name': apellidos,
          'company_code': codigoEmpresa,
          'pin': pin,
          'password': password,
        },
      );
      return _processResponse(response);
    } catch (e) {
      return {'status': 'error', 'message': 'Error al conectar con el servidor'};
    }
  }

  // 3. Servicio de Recuperación (Notificación al Admin)
  Future<Map<String, dynamic>> requestPasswordRecovery(String empresaId, String email) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.recoverPassword),
        body: {
          'empresa_id': empresaId,
          'email': email,
        },
      );
      return _processResponse(response);
    } catch (e) {
      return {'status': 'error', 'message': 'No se pudo enviar la solicitud'};
    }
  }

  // Función privada para procesar respuestas JSON
  Map<String, dynamic> _processResponse(http.Response response) {
    final Map<String, dynamic> data = json.decode(response.body);
    if (response.statusCode == 200 || response.statusCode == 201) {
      return {'status': 'success', 'data': data};
    } else {
      return {'status': 'error', 'message': data['message'] ?? 'Error desconocido'};
    }
  }
}