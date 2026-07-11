import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';
import '../../providers/theme_provider.dart';
import 'tabs/home_tab.dart';
import 'tabs/requests_tab.dart';
import 'tabs/history_tab.dart';
import 'tabs/notifications_tab.dart';
import 'tabs/profile_tab.dart';

class HomeView extends StatefulWidget {
  const HomeView({super.key});

  @override
  State<HomeView> createState() => _HomeViewState();
}

class _HomeViewState extends State<HomeView> with TickerProviderStateMixin {
  int _currentIndex = 0;

  final List<Widget> _pages = const [
    HomeTab(),
    RequestsTab(),
    HistoryTab(),
    NotificationsTab(),
    ProfileTab(),
  ];

  static const List<IconData> _activeIcons = [
    Icons.home,
    Icons.description,
    Icons.history,
    Icons.notifications,
    Icons.person,
  ];

  static const List<IconData> _inactiveIcons = [
    Icons.home_outlined,
    Icons.description_outlined,
    Icons.history,
    Icons.notifications_outlined,
    Icons.person_outlined,
  ];

  @override
  Widget build(BuildContext context) {
    final session = Provider.of<SessionProvider>(context);
    final theme = Provider.of<ThemeProvider>(context);

    return Scaffold(
      extendBody: true,
      appBar: AppBar(
        title: const Text('Panel Principal'),
        backgroundColor: theme.primaryDark,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => session.logout(),
          )
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: _pages,
      ),
      bottomNavigationBar: _buildBottomNav(theme),
    );
  }

  Widget _buildBottomNav(ThemeProvider theme) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 24),
      decoration: BoxDecoration(
        color: theme.primaryDark,
        borderRadius: BorderRadius.circular(35),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: SizedBox(
        height: 65,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: List.generate(5, (index) {
            final isActive = _currentIndex == index;
            return GestureDetector(
              onTap: () => setState(() => _currentIndex = index),
              behavior: HitTestBehavior.opaque,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                curve: Curves.easeInOut,
                width: isActive ? 50 : null,
                height: isActive ? 50 : null,
                transform: isActive
                    ? Matrix4.translationValues(0, -12, 0)
                    : Matrix4.identity(),
                decoration: BoxDecoration(
                  color: isActive ? Colors.white : Colors.transparent,
                  shape: BoxShape.circle,
                  boxShadow: isActive
                      ? [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.15),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ]
                      : null,
                ),
                child: Icon(
                  isActive ? _activeIcons[index] : _inactiveIcons[index],
                  color: isActive ? theme.primaryDark : Colors.white54,
                  size: 26,
                ),
              ),
            );
          }),
        ),
      ),
    );
  }
}
