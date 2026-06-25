class ApiConstants {
  // 1. Cambiamos el puerto al 5000 que es donde corre tu php artisan serve
  // Si usas el emulador de Android, 10.0.2.2 es correcto para apuntar a tu PC.
  static const String baseUrl = "http://10.0.2.2:5000/api"; 
  
  // 2. Apuntamos al endpoint correcto para Flutter (/mobile) que definiste en tu api.php
  static const String login = "$baseUrl/mobile"; 
  
  // Estos endpoints dependerán de cómo los nombres dentro de routes/mobile/route.php
  static const String register = "$baseUrl/mobile/register";
  static const String recoverPassword = "$baseUrl/mobile/password/recover";
  static const String checkStatus = "$baseUrl/mobile/user/status";
}
