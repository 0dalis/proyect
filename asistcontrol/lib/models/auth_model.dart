class AuthModel {
  final String token;
  final String userId;
  final bool isActive; // Flag para validar si el usuario puede entrar
  final String? message;

  AuthModel({
    required this.token,
    required this.userId,
    required this.isActive,
    this.message
  });

  factory AuthModel.fromJson(Map<String, dynamic> json) {
    return AuthModel(
      token: json['token'] ?? '',
      userId: json['user_id']?.toString() ?? '',
      isActive: json['is_active'] ?? false, // Laravel suele devolver booleanos o 1/0
      message: json['message'],
    );
  }
}
