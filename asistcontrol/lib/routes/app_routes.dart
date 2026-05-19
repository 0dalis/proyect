import 'package:flutter/material.dart';
// Importa tus vistas aquí
import '../views/welcome/splash_view.dart';
import '../views/login/login_view.dart';
import '../views/recuperarpassword/forgot_password_view.dart';

class AppRoutes {
  static const String splash = '/';
  static const String login = '/login';
  static const String forgotPassword = '/forgot-password';

  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case splash:
        return MaterialPageRoute(builder: (_) => const SplashView());
      case login:
        return MaterialPageRoute(builder: (_) => const LoginView());
      case forgotPassword:
        return MaterialPageRoute(builder: (_) => const ForgotPasswordView());
      default:
        return MaterialPageRoute(
          builder: (_) => Scaffold(
            body: Center(child: Text('No se definió la ruta para ${settings.name}')),
          ),
        );
    }
  }
}