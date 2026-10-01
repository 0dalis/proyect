import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../routes/app_routes.dart';
import '../../providers/session_provider.dart';
import '../../providers/theme_provider.dart';
import '../../services/api_service.dart';

class LoginView extends StatefulWidget {
  const LoginView({super.key});

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  final _idEmpresaController = TextEditingController();
  final _correoController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;

  void _handleLogin() async {
    final idEmpresa = _idEmpresaController.text.trim();
    final correo = _correoController.text.trim();
    final password = _passwordController.text.trim();

    if (idEmpresa.isEmpty || correo.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Por favor, complete todos los campos')),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final apiService = ApiService();
      final sessionProv = Provider.of<SessionProvider>(context, listen: false);

      final authResult = await apiService.login(idEmpresa, correo, password);

      if (authResult.isActive) {
        await sessionProv.setSession(authResult.token, authResult.userId);
        if (!mounted) return;

        if (authResult.isFirstTime) {
          Navigator.of(context).pushReplacementNamed(AppRoutes.completeProfile);
        } else {
          Navigator.of(context).pushReplacementNamed(AppRoutes.home);
        }
      } else {
        if (!mounted) return;
        _showErrorDialog('Acceso Denegado', 'Usuario inactivo. Contacte a su administrador');
      }
    } catch (e) {
      if (!mounted) return;
      _showErrorDialog('Error de Autenticación', e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showErrorDialog(String title, String message) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Aceptar'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      backgroundColor: theme.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center, // Logo y Bienvenido centrados
            children: [
              const SizedBox(height: 80),

              // LOGO CENTRADO
              Image.asset(
                'assets/logo_splash.png',
                height: 90,
                width: 90,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) {
                  return Icon(Icons.lock_person, size: 80, color: theme.primaryDark);
                },
              ),
              const SizedBox(height: 15),

              // TEXTOS CENTRADOS
              Text(
                "Bienvenido",
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: theme.primaryDark,
                ),
              ),
              const Text(
                "Ingresa tus datos para comenzar",
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16, color: Colors.grey),
              ),
              const SizedBox(height: 50),

              // FORMULARIO ALINEADO A LA IZQUIERDA
              SizedBox(
                width: double.infinity,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. CORREO
                    _buildInput(
                      label: "Correo",
                      hint: "correo@ejemplo.com",
                      icon: Icons.email_outlined,
                      controller: _correoController,
                      theme: theme,
                    ),
                    const SizedBox(height: 20),

                    // 2. CONTRASEÑA
                    _buildInput(
                      label: "Contraseña",
                      hint: "••••••••",
                      icon: Icons.lock_outline_rounded,
                      controller: _passwordController,
                      isPassword: true,
                      theme: theme,
                    ),
                    const SizedBox(height: 10),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: () => Navigator.pushNamed(context, AppRoutes.forgotPassword),
                        child: Text(
                          "¿Olvidaste tu contraseña?",
                          style: TextStyle(color: theme.accentBlue, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // 3. EMPRESA (Sin el "ID")
                    _buildInput(
                      label: "Empresa",
                      hint: "Ej: Mi Empresa",
                      icon: Icons.business_rounded,
                      controller: _idEmpresaController,
                      theme: theme,
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 40),

              // BOTÓN DE INGRESO
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _handleLogin,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.primaryDark,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                    elevation: 0,
                  ),
                  child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        "INGRESAR",
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 1.2,
                        ),
                      ),
                ),
              ),
              const SizedBox(height: 60),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInput({
    required String label,
    required String hint,
    required IconData icon,
    required TextEditingController controller,
    required ThemeProvider theme,
    bool isPassword = false,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        obscureText: isPassword ? _obscurePassword : false,
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: Colors.grey, fontSize: 15),
          floatingLabelStyle: TextStyle(
            color: theme.primaryDark,
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
          floatingLabelBehavior: FloatingLabelBehavior.auto,
          hintText: hint,
          hintStyle: const TextStyle(color: Colors.grey, fontSize: 14),
          prefixIcon: Icon(icon, color: theme.primaryMedium),
          suffixIcon: isPassword
              ? GestureDetector(
                  onLongPressStart: (_) => setState(() => _obscurePassword = false),
                  onLongPressEnd: (_) => setState(() => _obscurePassword = true),
                  child: Icon(
                    _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    color: theme.primaryMedium,
                  ),
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 15),
        ),
      ),
    );
  }
}
