import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:open_filex/open_filex.dart';

import '../../../providers/theme_provider.dart';
import '../../../providers/session_provider.dart';
import '../../../services/api_service.dart';

class ProfileTab extends StatefulWidget {
  const ProfileTab({super.key});

  @override
  State<ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<ProfileTab> {
  final ApiService _api = ApiService();
  bool _loading = false;

  Future<void> _downloadCredential() async {
    final session = context.read<SessionProvider>();
    final token = session.token;

    if (token == null) {
      _snack('Sesión no válida.');
      return;
    }

    setState(() => _loading = true);
    try {
      final info = await _api.getCredential(token);
      if (info['available'] != true) {
        _snack(info['message'] ?? 'Tu credencial aún no está disponible.');
        return;
      }
      final path = await _api.downloadCredentialPdf(token);
      await OpenFilex.open(path);
    } catch (e) {
      _snack('No se pudo descargar la credencial.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.person_outlined, size: 80, color: theme.primaryDark.withValues(alpha: 0.3)),
          const SizedBox(height: 16),
          Text(
            'Perfil',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600, color: theme.primaryDark),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _loading ? null : _downloadCredential,
            icon: _loading
                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.badge_outlined),
            label: Text(_loading ? 'Descargando...' : 'Mi credencial'),
            style: ElevatedButton.styleFrom(
              backgroundColor: theme.primaryDark,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              'Descarga tu credencial en PDF para imprimirla. Disponible si tu administrador lo autorizó.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
            ),
          ),
        ],
      ),
    );
  }
}
