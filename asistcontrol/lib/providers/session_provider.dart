import 'package:flutter/material.dart';
import '../core/services/secure_storage_service.dart';

enum SessionStatus { initial, authenticated, unauthenticated }

class SessionProvider with ChangeNotifier {
  final SecureStorageService _storage = SecureStorageService();
  SessionStatus _status = SessionStatus.initial;
  String? _token;
  String? _userId;

  SessionStatus get status => _status;
  String? get token => _token;
  String? get userId => _userId;

  Future<void> checkSession() async {
    final token = await _storage.getToken();
    final userId = await _storage.getUserId();

    if (token != null && userId != null) {
      _token = token;
      _userId = userId;
      _status = SessionStatus.authenticated;
    } else {
      _status = SessionStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<void> setSession(String token, String userId) async {
    await _storage.saveSession(token, userId);
    _token = token;
    _userId = userId;
    _status = SessionStatus.authenticated;
    notifyListeners();
  }

  // MÉTODO DE EMERGENCIA: Borra sesión y notifica a la app
  Future<void> forceLogout() async {
    await _storage.clearSession();
    _token = null;
    _userId = null;
    _status = SessionStatus.unauthenticated;
    notifyListeners();
  }

  Future<void> logout() async {
    await forceLogout();
  }
}
