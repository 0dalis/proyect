import 'dart:io';
import 'package:flutter/material.dart';
import 'package:lottie/lottie.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:asistcontrol/providers/theme_provider.dart';

class DynamicAppBackground extends StatelessWidget {
  final Widget child;

  const DynamicAppBackground({super.key, required this.child});

  Future<Widget> _buildBackground(BuildContext context) async {
    final directory = await getApplicationDocumentsDirectory();

    // Buscar archivo theme_font_sistem con cualquier extensión
    final List<FileSystemEntity> files = directory.listSync();
    final bgFile = files.firstWhereOrNull(
      (file) => file.path.contains('theme_font_sistem.'),
    );

    if (bgFile == null) {
      return Container(color: Provider.of<ThemeProvider>(context, listen: false).background);
    }

    final String extension = bgFile.path.split('.').last.toLowerCase();

    if (extension == 'json') {
      return Lottie.file(
        File(bgFile.path),
        fit: BoxFit.cover,
        width: double.infinity,
        height: double.infinity,
      );
    } else if (extension == 'gif' || extension == 'png' || extension == 'jpg' || extension == 'jpeg') {
      return Image.file(
        File(bgFile.path),
        fit: BoxFit.cover,
        width: double.infinity,
        height: double.infinity,
      );
    }

    return Container(color: Provider.of<ThemeProvider>(context, listen: false).background);
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        FutureBuilder<Widget>(
          future: _buildBackground(context),
          builder: (context, snapshot) {
            if (snapshot.hasData) {
              return snapshot.data!;
            }
            return Container(color: Provider.of<ThemeProvider>(context).background);
          },
        ),
        Positioned.fill(child: child),
      ],
    );
  }
}

// Helper for firstWhereOrNull to avoid adding extra dependencies
extension FirstWhereOrNull<T> on Iterable<T> {
  T? firstWhereOrNull(bool Function(T) test) {
    final iterator = this.iterator;
    while (iterator.moveNext()) {
      if (test(iterator.current)) return iterator.current;
    }
    return null;
  }
}
