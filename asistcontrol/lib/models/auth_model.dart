class AuthModel {
  final String token;
  final String userId;
  final bool isActive; // Flag para validar si el usuario puede entrar
  final bool isFirstTime;
  final String? message;

  AuthModel({
    required this.token,
    required this.userId,
    required this.isActive,
    required this.isFirstTime,
    this.message
  });

  factory AuthModel.fromJson(Map<String, dynamic> json) {
    return AuthModel(
      token: json['token'] ?? '',
      userId: json['user_id']?.toString() ?? '',
      isActive: json['is_active'] ?? false, // Laravel suele devolver booleanos o 1/0
      isFirstTime: json['is_first_time'] ?? false,
      message: json['message'],
    );
  }
}
