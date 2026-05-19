import 'package:flutter/material.dart';
import '../resources/theme/app_colors.dart'; // Asegúrate de importar tus colores
import '../../routes/app_routes.dart';

class LoginView extends StatelessWidget {
  const LoginView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 80),
              // Título con el color más fuerte
              const Text(
                "Bienvenido",
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: AppColors.primaryDark,
                ),
              ),
              const Text(
                "Ingresa tus datos de acceso",
                style: TextStyle(fontSize: 16, color: AppColors.placeholder),
              ),
              const SizedBox(height: 50),

              // Campo: ID Empresa
              _buildInput(
                label: "ID Empresa",
                hint: "Ej: EMP-123",
                icon: Icons.business_rounded,
              ),
              const SizedBox(height: 20),

              // Campo: Usuario
              _buildInput(
                label: "Usuario",
                hint: "Tu nombre de usuario",
                icon: Icons.person_outline_rounded,
              ),
              const SizedBox(height: 20),

              // Campo: Contraseña
              _buildInput(
                label: "Contraseña",
                hint: "••••••••",
                icon: Icons.lock_outline_rounded,
                isPassword: true,
              ),

              const SizedBox(height: 10),
              // Olvidé mi contraseña
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () {
                    Navigator.pushNamed(context, AppRoutes.forgotPassword);
                  },
                  child: const Text(
                    "¿Olvidaste tu contraseña?",
                    style: TextStyle(color: AppColors.accentBlue, fontWeight: FontWeight.w600),
                  ),
                ),
              ),

              const SizedBox(height: 40),

              // Botón de Ingreso
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: () {
                    // Lógica de autenticación
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryDark,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                    elevation: 0,
                  ),
                  child: const Text(
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

  // Widget reutilizable para los inputs (mantiene la consistencia)
  Widget _buildInput({
    required String label,
    required String hint,
    required IconData icon,
    bool isPassword = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: AppColors.primaryDark,
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
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
            obscureText: isPassword,
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: const TextStyle(color: AppColors.placeholder, fontSize: 14),
              prefixIcon: Icon(icon, color: AppColors.primaryMedium),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 15),
            ),
          ),
        ),
      ],
    );
  }
}