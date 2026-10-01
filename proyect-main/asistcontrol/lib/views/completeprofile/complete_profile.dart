import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../providers/theme_provider.dart';
import '../resources/theme/app_colors.dart';

class CompleteProfileView extends StatefulWidget {
  const CompleteProfileView({super.key});

  @override
  State<CompleteProfileView> createState() => _CompleteProfileViewState();
}

class _CompleteProfileViewState extends State<CompleteProfileView> {
  // Controladores y nodos de foco para los 6 dígitos del PIN
  final List<TextEditingController> _pinControllers =
      List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _pinFocusNodes = List.generate(6, (_) => FocusNode());

  // Controladores y nodos de foco para la confirmación
  final List<TextEditingController> _confirmControllers =
      List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _confirmFocusNodes =
      List.generate(6, (_) => FocusNode());

  // Gestión de la imagen de perfil
  File? _imageFile;
  final ImagePicker _picker = ImagePicker();

  // Estados de control
  bool _hasError = false;

  // Obtener el PIN completo como String
  String get _pin => _pinControllers.map((c) => c.text).join();
  String get _confirmPin => _confirmControllers.map((c) => c.text).join();

  @override
  void initState() {
    super.initState();
    for (int i = 0; i < 6; i++) {
      _pinControllers[i].addListener(_onPinChanged);
      _confirmControllers[i].addListener(_onConfirmChanged);
    }
  }

  void _onPinChanged() {
    setState(() {
      _validatePins();
    });
  }

  void _onConfirmChanged() {
    setState(() {
      _validatePins();
    });
  }

  void _validatePins() {
    final confirmLength = _confirmControllers
        .where((c) => c.text.isNotEmpty)
        .length;
    if (confirmLength == 6) {
      _hasError = (_pin != _confirmPin);
    } else {
      _hasError = false;
    }
  }

  bool get _isButtonEnabled {
    final pinComplete = _pinControllers.every((c) => c.text.isNotEmpty);
    final confirmComplete =
        _confirmControllers.every((c) => c.text.isNotEmpty);
    return pinComplete && confirmComplete && !_hasError;
  }

  void _onDigitChanged(String value, int index, bool isConfirm) {
    if (value.length == 1 && index < 5) {
      if (isConfirm) {
        _confirmFocusNodes[index + 1].requestFocus();
      } else {
        _pinFocusNodes[index + 1].requestFocus();
      }
    }
    else if (value.isEmpty && index > 0) {
      if (isConfirm) {
        _confirmFocusNodes[index - 1].requestFocus();
      } else {
        _pinFocusNodes[index - 1].requestFocus();
      }
    }
    setState(() {});
  }

  void _showImageSourceActionSheet(BuildContext context, ThemeProvider theme) {
    showModalBottomSheet(
      context: context,
      backgroundColor: theme.background,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: Icon(Icons.photo_camera, color: theme.primaryDark),
              title: Text('Tomar foto',
                  style: TextStyle(color: theme.primaryDark)),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: Icon(Icons.photo_library, color: theme.primaryDark),
              title: Text('Seleccionar del dispositivo',
                  style: TextStyle(color: theme.primaryDark)),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final XFile? pickedFile = await _picker.pickImage(
        source: source,
        maxWidth: 500,
        maxHeight: 500,
        imageQuality: 80,
      );
      if (pickedFile != null) {
        setState(() {
          _imageFile = File(pickedFile.path);
        });
      }
    } catch (e) {
      debugPrint("Error seleccionando imagen: $e");
    }
  }

  void _handleSaveProfile() {
    final finalPin = _pin;
    debugPrint("PIN Listo para guardar: $finalPin");
    if (_imageFile != null) {
      debugPrint("Foto cargada en ruta temporal: ${_imageFile!.path}");
    }
  }

  @override
  void dispose() {
    for (var c in _pinControllers) { c.dispose(); }
    for (var f in _pinFocusNodes) { f.dispose(); }
    for (var c in _confirmControllers) { c.dispose(); }
    for (var f in _confirmFocusNodes) { f.dispose(); }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      backgroundColor: theme.background,
      body: SafeArea(
        child: SizedBox.expand(
          child: Stack(
            children: [
              SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 30.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const SizedBox(height: 40),
                    Text(
                      "Completa tu Perfil",
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: theme.primaryDark,
                      ),
                    ),
                    const SizedBox(height: 30),

                    // AVATAR SELECTOR (Estirado 4px: de 110 a 114)
                    GestureDetector(
                      onTap: () => _showImageSourceActionSheet(context, theme),
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          Container(
                            width: 114,
                            height: 114,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: theme.primaryDark.withOpacity(0.08),
                              border: Border.all(
                                color: theme.primaryDark.withOpacity(0.4),
                                width: 2,
                              ),
                              image: _imageFile != null
                                  ? DecorationImage(
                                      image: FileImage(_imageFile!),
                                      fit: BoxFit.cover,
                                    )
                                  : null,
                            ),
                          ),
                          if (_imageFile == null)
                            Icon(
                              Icons.camera_alt_outlined,
                              size: 34, // Escalado proporcionalmente
                              color: theme.primaryDark.withOpacity(0.6),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      "Agrega una foto (opcional)",
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: Colors.grey.shade600,
                      ),
                    ),

                    // ELIMINADO EL DIVIDER DE AQUÍ
                    const SizedBox(height: 40),

                    // SECCIÓN ESTABLECE TU PIN
                    Text(
                      "Establece tu PIN",
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: theme.primaryDark,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      "Usa un PIN de 6 dígitos para ingresar a tu cuenta",
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                    ),
                    const SizedBox(height: 20),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        6,
                        (index) => _buildDigitInput(
                          index,
                          controller: _pinControllers[index],
                          focusNode: _pinFocusNodes[index],
                          isConfirm: false,
                          theme: theme,
                        ),
                      ),
                    ),

                    const SizedBox(height: 35),

                    // SECCIÓN CONFIRMA TU PIN
                    Text(
                      "Confirma tu PIN",
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: theme.primaryDark,
                      ),
                    ),
                    const SizedBox(height: 20),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        6,
                        (index) => _buildDigitInput(
                          index,
                          controller: _confirmControllers[index],
                          focusNode: _confirmFocusNodes[index],
                          isConfirm: true,
                          theme: theme,
                        ),
                      ),
                    ),

                    const SizedBox(height: 25),

                    // TARJETA INFORMATIVA
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: theme.primaryDark.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: theme.primaryDark.withOpacity(0.15),
                          width: 1,
                        ),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(
                            Icons.info_outline_rounded,
                            color: theme.primaryDark,
                            size: 20,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  "Este PIN será tu método de acceso.",
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: theme.primaryDark,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  "Puedes cambiarlo directamente en tu perfil.",
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // BOTÓN DE GUARDAR
                    SizedBox(
                      width: double.infinity,
                      height: 55,
                      child: ElevatedButton(
                        onPressed: _isButtonEnabled ? _handleSaveProfile : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _isButtonEnabled
                              ? theme.primaryDark
                              : Colors.grey.shade300,
                          foregroundColor: Colors.white,
                          disabledBackgroundColor: Colors.grey.shade300,
                          disabledForegroundColor: Colors.grey.shade500,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(15)),
                          elevation: 0,
                        ),
                        child: const Text(
                          "GUARDAR PERFIL",
                          style: TextStyle(
                              fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                    const SizedBox(height: 60),
                  ],
                ),
              ),

              // MARCA DE AGUA FIJA
              Positioned(
                bottom: 12,
                right: 16,
                child: Opacity(
                  opacity: 0.35,
                  child: Text(
                    "powered by Jaly-Sistems",
                    style: TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.w500,
                      color: theme.primaryDark,
                      letterSpacing: 0.5,
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

  Widget _buildDigitInput(
    int index, {
    required TextEditingController controller,
    required FocusNode focusNode,
    required bool isConfirm,
    required ThemeProvider theme,
  }) {
    final hasDigit = controller.text.isNotEmpty;

    // Paleta de colores para el efecto neón
    const Color neonGreen = Color(0xff76ff03);
    const Color neonRed = Colors.red;

    final Color borderColor;
    List<BoxShadow> shadows = [];

    if (isConfirm && _hasError) {
      borderColor = neonRed;
      if (hasDigit) {
        shadows = [
          BoxShadow(color: neonRed.withOpacity(0.6), blurRadius: 10, spreadRadius: 1),
          BoxShadow(color: neonRed.withOpacity(0.3), blurRadius: 4, spreadRadius: -1),
        ];
      }
    } else if (hasDigit) {
      borderColor = neonGreen;
      shadows = [
        BoxShadow(color: neonGreen.withOpacity(0.6), blurRadius: 10, spreadRadius: 1),
        BoxShadow(color: neonGreen.withOpacity(0.3), blurRadius: 4, spreadRadius: -1),
      ];
    } else {
      borderColor = Colors.grey.shade400;
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeInOut,
        width: 48,
        height: 56,
        decoration: BoxDecoration(
          color: Colors.white, // Background siempre blanco como solicitaste
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: borderColor, width: 2),
          boxShadow: shadows,
        ),
        child: Center(
          child: TextField(
            controller: controller,
            focusNode: focusNode,
            textAlign: TextAlign.center,
            textAlignVertical: TextAlignVertical.center, // Centrado geométrico vertical
            maxLength: 1,
            keyboardType: TextInputType.number,
            // Ofuscación del PIN para que se comporte como contraseña uno por uno
            obscureText: true,
            obscuringCharacter: '●',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              height: 1.0,
              color: isConfirm && _hasError ? neonRed : theme.primaryDark,
            ),
            decoration: const InputDecoration(
              counterText: '',
              border: InputBorder.none,
              isCollapsed: true,
              contentPadding: EdgeInsets.zero,
            ),
            onChanged: (value) => _onDigitChanged(value, index, isConfirm),
          ),
        ),
      ),
    );
  }
}
