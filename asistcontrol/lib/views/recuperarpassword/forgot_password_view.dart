import 'package:flutter/material.dart';
import '../resources/theme/app_colors.dart';
import 'package:provider/provider.dart';
import '../../providers/theme_provider.dart';

class ForgotPasswordView extends StatelessWidget {
  const ForgotPasswordView({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      backgroundColor: theme.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new, color: theme.primaryDark),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 20),
              Text(
                "Recuperar\nContraseña",
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: theme.primaryDark,
                  height: 1.2,
                ),
              ),
              const SizedBox(height: 20),

              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: theme.accentBlue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(15),
                  border: Border.all(color: theme.accentBlue.withOpacity(0.3)),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: theme.accentBlue),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        "Se le notificará al administrador para el restablecimiento de tu contraseña. Una vez autorizado, recibirás un correo con tu nueva contraseña.",
                        style: TextStyle(
                          fontSize: 13,
                          color: theme.primaryDark,
                          height: 1.4,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 40),

              _buildInput(
                label: "ID Empresa",
                hint: "Ej: EMP-123",
                icon: Icons.business_rounded,
                theme: theme,
              ),
              const SizedBox(height: 25),

              _buildInput(
                label: "Correo Electrónico",
                hint: "usuario@empresa.com",
                icon: Icons.email_outlined,
                theme: theme,
              ),

              const SizedBox(height: 20),

              const Text(
                "* Recuerda usar el correo que proporcionaste originalmente a tu empresa.",
                style: TextStyle(
                  fontSize: 12,
                  fontStyle: FontStyle.italic,
                  color: AppColors.placeholder,
                ),
              ),

              const SizedBox(height: 50),

              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: () {
                    // Lógica para enviar notificación al administrador vía Laravel
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.primaryDark,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                    elevation: 0,
                  ),
                  child: const Text(
                    "SOLICITAR",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
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
    required ThemeProvider theme,
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
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 15),
        ),
      ),
    );
  }
}
