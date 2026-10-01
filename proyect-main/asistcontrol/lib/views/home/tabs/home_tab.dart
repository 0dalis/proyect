import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/session_provider.dart';
import '../../../providers/theme_provider.dart';
import '../../../services/api_service.dart';

class HomeTab extends StatelessWidget {
  const HomeTab({super.key});

  @override
  Widget build(BuildContext context) {
    final session = Provider.of<SessionProvider>(context);
    final theme = Provider.of<ThemeProvider>(context);
    final apiService = ApiService();

    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text('Bienvenido Usuario: ${session.userId}', style: const TextStyle(fontSize: 18)),
          const SizedBox(height: 20),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: theme.successGreen),
            onPressed: () async {
              try {
                final result = await apiService.registrarAsistencia(
                  session.token!,
                  session.userId!,
                  {'lat': '0.0', 'lng': '0.0', 'timestamp': DateTime.now().toString()}
                );

                if (result['update_theme'] == true) {
                  await apiService.updateDynamicTheme(session.token!, session.userId!);
                  if (!context.mounted) return;
                  final themeProv = Provider.of<ThemeProvider>(context, listen: false);
                  await themeProv.loadColorsFromJson();
                }

                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Asistencia registrada con éxito')),
                );
              } catch (e) {
                if (!context.mounted) return;
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
    );
  }
}
