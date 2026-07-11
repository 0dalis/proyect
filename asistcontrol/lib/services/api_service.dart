import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:flutter/foundation.dart'; // Import esencial para debugPrint

import 'api_constants.dart';
import '../core/errors/exceptions.dart';
import '../models/auth_model.dart';
import '../models/app_config_model.dart';

class ApiService {
  // LOGIN
  Future<AuthModel> login(String idEmpresa, String correo, String password) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.login),
        headers: {
          'Accept': 'application/json',
        },
        body: {
          'id_empresa': idEmpresa,
          'correo': correo,
          'password': password,
        },
      );
      return _handleResponse(response, (data) => AuthModel.fromJson(data));
    } catch (e) {
      throw _processError(e);
    }
  }

  // REGISTRAR ASISTENCIA
  Future<Map<String, dynamic>> registrarAsistencia(String token, String userId, Map<String, dynamic> data) async {
    try {
      final response = await http.post(
        Uri.parse("${ApiConstants.baseUrl}/asistencia/registrar"),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: {
          'user_id': userId,
          ...data,
        },
      );

      if (response.statusCode == 401) {
        throw AuthException('Sesión expirada o usuario inactivo');
      }

      final Map<String, dynamic> responseData = json.decode(response.body);

      if (responseData['is_active'] == false) {
        throw AuthException('Usuario inactivo. Contacte a su administrador');
      }

      return responseData;
    } catch (e) {
      throw _processError(e);
    }
  }

  // ACTUALIZACIÓN PEREZOSA (Lazy Update)
  Future<void> updateDynamicTheme(String token, String userId) async {
    try {
      final config = await getAppConfig(token, userId);
      await downloadDynamicResources(config);
    } catch (e) {
      debugPrint('Error actualizando tema estacional: $e');
    }
  }

  // --- MÉTODOS AUXILIARES ---

  Future<AppConfigModel> getAppConfig(String token, String userId) async {
    try {
      final response = await http.post(
        Uri.parse("http://10.0.2.2:5000/api/mobile/empresa/fonts"),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
        body: {'user_id': userId},
      );
      return _handleResponse(response, (data) => AppConfigModel.fromJson(data));
    } catch (e) {
      throw _processError(e);
    }
  }

  Future<void> downloadDynamicResources(AppConfigModel config) async {
    final directory = await getApplicationDocumentsDirectory();
    await _downloadFile(config.colorsUrl, '${directory.path}/app_color.json');
    await _downloadFile(config.splashUrl, '${directory.path}/loading_font.${config.splashExtension}');
    await _downloadFile(config.themeBackgroundUrl, '${directory.path}/theme_font_sistem.${config.themeBackgroundExtension}');
  }

  Future<void> _downloadFile(String url, String path) async {
    if (url.isEmpty) return;
    try {
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        final file = File(path);
        await file.writeAsBytes(response.bodyBytes);
      }
    } catch (e) {
      debugPrint('Download error: $e');
    }
  }

  T _handleResponse<T>(http.Response response, T Function(Map<String, dynamic>) mapper) {
    final Map<String, dynamic> data = json.decode(response.body);
    if (response.statusCode == 200 || response.statusCode == 201) return mapper(data);
    throw AuthException(data['message'] ?? 'Error desconocido');
  }

  // Cambiamos de void a AppException para que el 'throw' sea explícito en el flujo
  AppException _processError(dynamic e) {
    if (e is AppException) return e;
    if (e is http.ClientException) return NetworkException('Sin conexión');
    return AppException('Error inesperado: ${e.toString()}');
  }
}
