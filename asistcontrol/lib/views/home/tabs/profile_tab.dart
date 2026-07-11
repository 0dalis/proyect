import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/theme_provider.dart';

class ProfileTab extends StatelessWidget {
  const ProfileTab({super.key});

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
          const SizedBox(height: 8),
          Text(
            'Próximamente',
            style: TextStyle(fontSize: 14, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }
}
