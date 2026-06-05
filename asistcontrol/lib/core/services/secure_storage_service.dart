import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../errors/exceptions.dart';

class SecureStorageService {
  // Eliminamos 'const' para evitar errores de resolución de métodos en Web/Desktop
  final _storage = FlutterSecureStorage();

  static const String keyToken = 'jwt_token';
  static const String keyUserId = 'user_id';

  Future<void> saveSession(String token, String userId) async {
    try {
      await _storage.write(key: keyToken, value: token);
      await _storage.write(key: keyUserId, value: userId);
    } catch (e) {
      throw StorageException('Error saving session to secure storage');
    }
  }

  Future<String?> getToken() async {
    return await _storage.read(key: keyToken);
  }

  Future<String?> getUserId() async {
    return await _storage.read(key: keyUserId);
  }

  Future<void> clearSession() async {
    try {
      await _storage.delete(key: keyToken);
      await _storage.delete(key: keyUserId);
    } catch (e) {
      throw StorageException('Error clearing session');
    }
  }

  Future<bool> hasToken() async {
    return await _storage.containsKey(key: keyToken);
  }
}
