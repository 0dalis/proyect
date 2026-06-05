import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import '../models/app_config_model.dart';
import '../views/resources/theme/app_colors.dart';

class ThemeProvider with ChangeNotifier {
  // Default colors (Fallback)
  Color _primaryDark = AppColors.primaryDark;
  Color _primaryMedium = AppColors.primaryMedium;
  Color _primaryLight = AppColors.primaryLight;
  Color _accentBlue = AppColors.accentBlue;
  Color _accentTeal = AppColors.accentTeal;
  Color _successGreen = AppColors.successGreen;
  Color _background = Colors.white; // Requerimiento: Fondo BLANCO por defecto

  Color get primaryDark => _primaryDark;
  Color get primaryMedium => _primaryMedium;
  Color get primaryLight => _primaryLight;
  Color get accentBlue => _accentBlue;
  Color get accentTeal => _accentTeal;
  Color get successGreen => _successGreen;
  Color get background => _background;

  Future<void> loadColorsFromJson() async {
    try {
      final directory = await getApplicationDocumentsDirectory();
      final file = File('${directory.path}/app_color.json');

      if (await file.exists()) {
        final contents = await file.readAsString();
        final Map<String, dynamic> colorsData = json.decode(contents);

        _updateFromMap(colorsData);
      }
    } catch (e) {
      debugPrint('Error loading colors from JSON: $e');
    }
    notifyListeners();
  }

  void _updateFromMap(Map<String, dynamic> data) {
    if (data.containsKey('primaryDark')) _primaryDark = AppConfigModel.hexToColor(data['primaryDark']);
    if (data.containsKey('primaryMedium')) _primaryMedium = AppConfigModel.hexToColor(data['primaryMedium']);
    if (data.containsKey('primaryLight')) _primaryLight = AppConfigModel.hexToColor(data['primaryLight']);
    if (data.containsKey('accentBlue')) _accentBlue = AppConfigModel.hexToColor(data['accentBlue']);
    if (data.containsKey('accentTeal')) _accentTeal = AppConfigModel.hexToColor(data['accentTeal']);
    if (data.containsKey('successGreen')) _successGreen = AppConfigModel.hexToColor(data['successGreen']);
    if (data.containsKey('background')) _background = AppConfigModel.hexToColor(data['background']);
  }
}
