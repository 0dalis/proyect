import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';
import '../../providers/theme_provider.dart';
import '../../services/api_service.dart';

class HomeView extends StatelessWidget {
  const HomeView({super.key});

  @override
  Widget build(BuildContext context) {
    final session = Provider.of<SessionProvider>(context);
    final theme = Provider.of<ThemeProvider>(context);
    final apiService = ApiService();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Panel Principal'),
        backgroundColor: theme.primaryDark,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => session.logout(),
          )
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Bienvenido Usuario: ${session.userId}', style: const TextStyle(fontSize: 18)),
            const SizedBox(height: 20),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: theme.successGreen),
              onPressed: () async {
                try {
                  // Simulación de registro de asistencia
                  final result = await apiService.registrarAsistencia(
                    session.token!,
                    session.userId!,
                    {'lat': '0.0', 'lng': '0.0', 'timestamp': DateTime.now().toString()}
                  );

                  // LÓGICA de actualización perezosa (Lazy Update)
                  if (result['update_theme'] == true) {
                    await apiService.updateDynamicTheme(session.token!, session.userId!);
                    // Actualizar colores reactivamente en la UI
                    final themeProv = Provider.of<ThemeProvider>(context, listen: false);
                    await themeProv.loadColorsFromJson();
                  }

                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Asistencia registrada con éxito')),
                  );
                } catch (e) {
                  // Si es un error de autenticación/inactivo, forzamos el logout
                  if (e.toString().contains('Sesión expirada') || e.toString().contains('Usuario inactivo')) {
                    session.forceLogout();
                    Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Sesión expirada o usuario inactivo')),
                    );
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Error: ${e.toString()}')),
                    );
                  }
                }
              },
              child: const Text('Registrar Asistencia'),
            ),
          ],
        ),
      ),
    );
  }
}
