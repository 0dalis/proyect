import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:path_provider/path_provider.dart';
import '../../routes/app_routes.dart';
import '../../providers/session_provider.dart';
import '../../providers/theme_provider.dart';
import '../../services/api_service.dart';

class SplashView extends StatefulWidget {
  const SplashView({super.key});

  @override
  State<SplashView> createState() => _SplashViewState();
}

class _SplashViewState extends State<SplashView> {
  String? _localImagePath;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    final sessionProv = Provider.of<SessionProvider>(context, listen: false);
    final themeProv = Provider.of<ThemeProvider>(context, listen: false);
    final apiService = ApiService();

    try {
      await sessionProv.checkSession();
    } catch (e) {
      debugPrint('Error al verificar sesión: $e');
    }

    if (sessionProv.status == SessionStatus.authenticated) {
      try {
        final config = await apiService.getAppConfig(sessionProv.token!, sessionProv.userId!);
        await apiService.downloadDynamicResources(config);
      } catch (e) {
        debugPrint('Error cargando recursos remotos: $e');
      }

      try {
        await themeProv.loadColorsFromJson();
      } catch (e) {
        debugPrint('Error cargando colores locales: $e');
      }

      try {
        final directory = await getApplicationDocumentsDirectory();
        final files = directory.listSync();
        final loadingFile = files.firstWhereOrNull((f) => f.path.contains('loading_font.'));

        if (loadingFile != null) {
          setState(() {
            _localImagePath = loadingFile.path;
          });
        }
      } catch (e) {
        debugPrint('Error cargando splash local: $e');
      }

      await Future.delayed(const Duration(seconds: 4));

      if (!mounted) return;
      Navigator.of(context).pushReplacementNamed(AppRoutes.home);
    } else {
      await Future.delayed(const Duration(seconds: 3));
      if (!mounted) return;
      Navigator.of(context).pushReplacementNamed(AppRoutes.login);
    }

    if (mounted) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      backgroundColor: theme.background,
      body: Stack(
        children: [
          // El contenido siempre es visible
          Center(child: _buildContent(theme)),

          // El spinner se muestra como un overlay mientras carga
          if (_isLoading)
            Center(
              child: CircularProgressIndicator(
                color: theme.primaryDark,
                strokeWidth: 3,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildContent(ThemeProvider theme) {
    if (_localImagePath != null) {
      return SizedBox.expand(
        child: Image.file(
          File(_localImagePath!),
          fit: BoxFit.cover,
        ),
      );
    }

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.lock_person, size: 100, color: Colors.blue),
        const SizedBox(height: 20),
        Text(
          'AsistControl',
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: theme.primaryDark,
          ),
        ),
        const SizedBox(height: 30),
        // Quitamos el spinner de aquí para que no haya duplicados,
        // ya que ahora está en la capa superior del Stack.
      ],
    );
  }
}

extension FirstWhereOrNull<T> on Iterable<T> {
  T? firstWhereOrNull(bool Function(T) test) {
    final iterator = this.iterator;
    while (iterator.moveNext()) {
      if (test(iterator.current)) return iterator.current;
    }
    return null;
  }
}
