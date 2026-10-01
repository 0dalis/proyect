class ApiConstants {
  // 1. Cambiamos el puerto al 5000 que es donde corre tu php artisan serve
  // Si usas el emulador de Android, 10.0.2.2 es correcto para apuntar a tu PC.
  static const String baseUrl = "http://10.0.2.2:5000/api"; 
  
  // 2. Apuntamos al endpoint correcto para Flutter (/mobile) que definiste en tu api.php
  static const String login = "$baseUrl/mobile"; 
  
  // Endpoints móviles (routes/mobile/route.php)
  static const String attendance = "$baseUrl/mobile/asistencia/registrar";
  static const String history = "$baseUrl/mobile/attendance/history";
  static const String requests = "$baseUrl/mobile/requests";
  static const String notifications = "$baseUrl/mobile/notifications";
  static const String appConfig = "$baseUrl/mobile/empresa/fonts";
  static const String deviceToken = "$baseUrl/mobile/device-token";
  static const String logout = "$baseUrl/mobile/logout";

  static const String credential = "$baseUrl/mobile/credential";
  static const String credentialPdf = "$baseUrl/mobile/credential/pdf";

  static const String register = "$baseUrl/mobile/register";
  static const String recoverPassword = "$baseUrl/mobile/password/recover";
  static const String checkStatus = "$baseUrl/mobile/user/status";
}
