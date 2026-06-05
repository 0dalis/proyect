import 'package:flutter/material.dart';

class AppConfigModel {
  final String splashUrl;
  final String splashExtension;
  final String colorsUrl;
  final String themeBackgroundUrl;
  final String themeBackgroundExtension;

  AppConfigModel({
    required this.splashUrl,
    required this.splashExtension,
    required this.colorsUrl,
    required this.themeBackgroundUrl,
    required this.themeBackgroundExtension,
  });

  factory AppConfigModel.fromJson(Map<String, dynamic> json) {
    return AppConfigModel(
      splashUrl: json['splash_url'] ?? '',
      splashExtension: json['splash_ext'] ?? 'png',
      colorsUrl: json['colors_url'] ?? '',
      themeBackgroundUrl: json['theme_bg_url'] ?? '',
      themeBackgroundExtension: json['theme_bg_ext'] ?? 'png',
    );
  }

  static Color hexToColor(String hexString) {
    final buffer = StringBuffer();
    if (hexString.length == 6 || hexString.length == 7) buffer.write('ff');
    buffer.write(hexString.replaceFirst('#', ''));
    try {
      return Color(int.parse(buffer.toString(), radix: 16));
    } catch (_) {
      return Colors.white;
    }
  }
}
