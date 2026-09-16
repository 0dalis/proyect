import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import 'api_constants.dart';

/// Integración con Firebase Cloud Messaging.
///
/// Requiere los archivos de configuración de Firebase:
/// - Android: android/app/google-services.json
/// - iOS: ios/Runner/GoogleService-Info.plist
///
/// Si no están presentes, la app funciona en modo degradado (sin push).
class PushNotificationService {
  static bool _initialized = false;

  static Future<void> init(String? authToken) async {
    if (_initialized || authToken == null) return;

    try {
      await Firebase.initializeApp();
      final messaging = FirebaseMessaging.instance;

      await messaging.requestPermission(alert: true, badge: true, sound: true);

      final fcmToken = await messaging.getToken();
      if (fcmToken != null) {
        await _registerToken(authToken, fcmToken);
      }

      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        debugPrint('FCM (foreground): ${message.notification?.title} - ${message.notification?.body}');
      });

      messaging.onTokenRefresh.listen((newToken) {
        _registerToken(authToken, newToken);
      });

      _initialized = true;
    } catch (e) {
      debugPrint('FCM no configurado o error al inicializar: $e');
    }
  }

  static Future<void> _registerToken(String authToken, String deviceToken) async {
    try {
      await http.post(
        Uri.parse(ApiConstants.deviceToken),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
        body: {'device_token': deviceToken},
      );
      debugPrint('Token de dispositivo registrado.');
    } catch (e) {
      debugPrint('No se pudo registrar el token del dispositivo: $e');
    }
  }
}
