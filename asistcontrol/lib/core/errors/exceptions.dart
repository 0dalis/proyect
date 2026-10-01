import 'package:flutter/material.dart';

class AppException implements Exception {
  final String message;
  final String? code;

  AppException(this.message, [this.code]);

  @override
  String toString() => 'AppException: $message (Code: $code)';
}

class NetworkException extends AppException {
  NetworkException(String message) : super(message, 'NETWORK_ERROR');
}

class AuthException extends AppException {
  AuthException(String message) : super(message, 'AUTH_ERROR');
}

class StorageException extends AppException {
  StorageException(String message) : super(message, 'STORAGE_ERROR');
}
