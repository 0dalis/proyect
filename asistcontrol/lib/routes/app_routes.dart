import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/session_provider.dart';
import '../views/welcome/splash_view.dart';
import '../views/login/login_view.dart';
import '../views/recuperarpassword/forgot_password_view.dart';
import '../views/home/home_view.dart';
import '../views/completeprofile/complete_profile.dart';

class AppRoutes {
  static const String splash = '/';
  static const String login = '/login';
  static const String forgotPassword = '/forgot-password';

  // Agrupamos conceptualmente las rutas protegidas bajo un prefijo o las listamos
  static const String home = '/home';
  static const String completeProfile = '/complete-profile';

  static Route<dynamic> generateRoute(RouteSettings settings) {
    // 1. LISTA DE RUTAS PROTEGIDAS (Como un AuthGuard colectivo de Angular)
    final rutasProtegidas = [home, completeProfile];

    // 2. INTERCEPTOR COLECTIVO
    if (rutasProtegidas.contains(settings.name)) {
      return MaterialPageRoute(
        builder: (context) {
          final session = Provider.of<SessionProvider>(context, listen: false);

          // Si intenta acceder a Home o CompleteProfile sin estar Autenticado -> Rebote al Login
          if (session.status != SessionStatus.authenticated) {
            return const LoginView();
          }

          // Si pasó la validación del token, evaluamos qué vista entregar de forma segura
          return _buildProtectedView(settings.name);
        },
      );
    }

    // 3. RUTAS PÚBLICAS (No requieren token)
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

  // MÉTODO AUXILIAR: Devuelve la vista real una vez que el Guard colectivo autorizó el acceso
  static Widget _buildProtectedView(String? routeName) {
    switch (routeName) {
      case home:
        return const HomeView();
      case completeProfile:
        return const CompleteProfileView(); // Cambiar por const CompleteProfileView()
      default:
        return const Scaffold(body: Center(child: Text('Error de enrutamiento protegido')));
    }
  }
}
