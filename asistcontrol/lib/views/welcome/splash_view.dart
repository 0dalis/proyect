import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:path_provider/path_provider.dart';
import '../../routes/app_routes.dart';
import '../../providers/session_provider.dart';
import '../../providers/theme_provider.dart';
import '../../services/api_service.dart';
import '../../models/app_config_model.dart';

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

      if (sessionProv.status == SessionStatus.authenticated) {
        // 1. Get config and download everything
        final config = await apiService.getAppConfig(sessionProv.token!, sessionProv.userId!);
        await apiService.downloadDynamicResources(config);

        // 2. Apply colors from the downloaded JSON
        await themeProv.loadColorsFromJson();

        // 3. Resolve loading font path (check extensions)
        final directory = await getApplicationDocumentsDirectory();
        final files = directory.listSync();
        final loadingFile = files.firstWhereOrNull((f) => f.path.contains('loading_font.'));

        if (loadingFile != null) {
          setState(() {
            _localImagePath = loadingFile.path;
          });
        }

        // Aumentamos la espera a 4 segundos para que se aprecie el Splash dinámico
        await Future.delayed(const Duration(seconds: 4));

        // Aquí redirigirías a Home
        if (!mounted) return;
        Navigator.of(context).pushReplacementNamed(AppRoutes.home);
      } else {
        // Para usuarios no autenticados, también damos un tiempo de cortesía para ver la marca
        await Future.delayed(const Duration(seconds: 3));
        if (!mounted) return;
        Navigator.of(context).pushReplacementNamed(AppRoutes.login);
        return;
      }
    } catch (e) {

      debugPrint('Error de inicialización: $e');
      if (!mounted) return;
      Navigator.of(context).pushReplacementNamed(AppRoutes.login);
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      backgroundColor: theme.background,
      body: Center(
        child: _isLoading
          ? const CircularProgressIndicator()
          : _buildContent(theme),
      ),
    );
  }

  Widget _buildContent(ThemeProvider theme) {
    if (_localImagePath != null) {
      // Flutter's Image.file handles both .png and .gif (animated) automatically
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
        const CircularProgressIndicator(),
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
