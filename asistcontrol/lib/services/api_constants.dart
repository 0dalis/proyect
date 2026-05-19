class ApiConstants {
  // Si usas el simulador de Android, 10.0.2.2 apunta al localhost de tu PC
  static const String baseUrl = "http://10.0.2.2:8000/api"; 
  
  static const String login = "$baseUrl/login";
  static const String register = "$baseUrl/register";
  static const String recoverPassword = "$baseUrl/password/recover";
  static const String checkStatus = "$baseUrl/user/status";
}