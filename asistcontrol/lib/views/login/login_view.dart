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
        // Usuario Activo -> Guardar sesión y entrar
        await sessionProv.setSession(authResult.token, authResult.userId);
        if (!mounted) return;
        Navigator.of(context).pushReplacementNamed(AppRoutes.home);
      } else {
        // Usuario Inactivo -> Bloquear acceso
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
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 80),
              Text(
                "Bienvenido",
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: theme.primaryDark,
                ),
              ),
              const Text(
                "Ingresa tus datos de acceso",
                style: TextStyle(fontSize: 16, color: Colors.grey),
              ),
              const SizedBox(height: 50),
              _buildInput(
                label: "ID Empresa",
                hint: "Ej: EMP-123",
                icon: Icons.business_rounded,
                controller: _idEmpresaController,
                theme: theme,
              ),
              const SizedBox(height: 20),
              _buildInput(
                label: "Correo",
                hint: "correo@ejemplo.com",
                icon: Icons.email_outlined,
                controller: _correoController,
                theme: theme,
              ),
              const SizedBox(height: 20),
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
              const SizedBox(height: 40),
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: theme.primaryDark,
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 8),
        Container(
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
            obscureText: isPassword,
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: const TextStyle(color: Colors.grey, fontSize: 14),
              prefixIcon: Icon(icon, color: theme.primaryMedium),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 15),
            ),
          ),
        ),
      ],
    );
  }
}
