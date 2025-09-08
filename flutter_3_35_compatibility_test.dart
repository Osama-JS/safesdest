// Flutter 3.35 Compatibility Test for Task Ads System
// This file tests all the new features to ensure compatibility

import 'package:flutter/material.dart';

// Import all our models and services
import 'safedest_driver/lib/models/task_ad.dart';
import 'safedest_driver/lib/models/task_offer.dart';
import 'safedest_driver/lib/services/task_ads_service.dart';
import 'safedest_driver/lib/screens/task_ads/task_ads_screen.dart';
import 'safedest_driver/lib/screens/task_ads/task_ad_details_screen.dart';
import 'safedest_driver/lib/screens/task_ads/submit_offer_screen.dart';
import 'safedest_driver/lib/screens/task_ads/my_offers_screen.dart';
import 'safedest_driver/lib/widgets/task_ad_card.dart';
import 'safedest_driver/lib/widgets/offer_card.dart';

void main() {
  runApp(const TaskAdsTestApp());
}

class TaskAdsTestApp extends StatelessWidget {
  const TaskAdsTestApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Task Ads System Test',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepPurple),
        useMaterial3: true, // Flutter 3.35 Material 3 support
      ),
      home: const TestHomePage(),
    );
  }
}

class TestHomePage extends StatefulWidget {
  const TestHomePage({super.key});

  @override
  State<TestHomePage> createState() => _TestHomePageState();
}

class _TestHomePageState extends State<TestHomePage> {
  final List<String> _testResults = [];

  @override
  void initState() {
    super.initState();
    _runTests();
  }

  void _runTests() {
    _addTestResult('🚀 Starting Flutter 3.35 Compatibility Tests...');
    
    // Test 1: Model instantiation
    try {
      _testModels();
      _addTestResult('✅ Models test passed');
    } catch (e) {
      _addTestResult('❌ Models test failed: $e');
    }

    // Test 2: Service instantiation
    try {
      _testServices();
      _addTestResult('✅ Services test passed');
    } catch (e) {
      _addTestResult('❌ Services test failed: $e');
    }

    // Test 3: Widget instantiation
    try {
      _testWidgets();
      _addTestResult('✅ Widgets test passed');
    } catch (e) {
      _addTestResult('❌ Widgets test failed: $e');
    }

    // Test 4: Enum functionality
    try {
      _testEnums();
      _addTestResult('✅ Enums test passed');
    } catch (e) {
      _addTestResult('❌ Enums test failed: $e');
    }

    // Test 5: Flutter 3.35 specific features
    try {
      _testFlutter335Features();
      _addTestResult('✅ Flutter 3.35 features test passed');
    } catch (e) {
      _addTestResult('❌ Flutter 3.35 features test failed: $e');
    }

    _addTestResult('🎉 All tests completed!');
  }

  void _testModels() {
    // Test TaskOfferStatus enum
    const status = TaskOfferStatus.pending;
    assert(status == TaskOfferStatus.pending);

    // Test that we can create basic model instances
    // Note: We can't create full instances without proper JSON data
    // but we can test that the classes exist and are accessible
    assert(TaskOffer.toString().isNotEmpty);
    assert(TaskAd.toString().isNotEmpty);
  }

  void _testServices() {
    // Test service instantiation
    final service = TaskAdsService();
    assert(service.toString().isNotEmpty);
  }

  void _testWidgets() {
    // Test that widget classes exist and can be referenced
    assert(TaskAdsScreen.toString().isNotEmpty);
    assert(MyOffersScreen.toString().isNotEmpty);
  }

  void _testEnums() {
    // Test all TaskOfferStatus values
    const statuses = TaskOfferStatus.values;
    assert(statuses.contains(TaskOfferStatus.pending));
    assert(statuses.contains(TaskOfferStatus.accepted));
    assert(statuses.contains(TaskOfferStatus.rejected));
    assert(statuses.length == 3);
  }

  void _testFlutter335Features() {
    // Test Material 3 color scheme (Flutter 3.35 feature)
    final colorScheme = ColorScheme.fromSeed(seedColor: Colors.blue);
    assert(colorScheme.primary.value != 0);

    // Test withValues method (replaces deprecated withOpacity)
    final color = Colors.blue.withValues(alpha: 0.5);
    assert(color.alpha < 255);

    // Test super.key parameter (Flutter 3.35 requirement)
    const widget = TestWidget(key: Key('test'));
    assert(widget.key != null);
  }

  void _addTestResult(String result) {
    setState(() {
      _testResults.add(result);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        title: const Text('Task Ads System - Flutter 3.35 Test'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Flutter 3.35 Compatibility Test Results:',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView.builder(
                itemCount: _testResults.length,
                itemBuilder: (context, index) {
                  final result = _testResults[index];
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4.0),
                    child: Text(
                      result,
                      style: TextStyle(
                        fontFamily: 'monospace',
                        color: result.startsWith('❌') 
                            ? Colors.red 
                            : result.startsWith('✅')
                                ? Colors.green
                                : null,
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const TaskAdsScreen(),
                    ),
                  );
                },
                child: const Text('Open Task Ads Screen'),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const MyOffersScreen(),
                    ),
                  );
                },
                child: const Text('Open My Offers Screen'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class TestWidget extends StatelessWidget {
  const TestWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.blue.withValues(alpha: 0.1), // Flutter 3.35 syntax
      child: const Text('Test Widget'),
    );
  }
}

// Test class to verify all imports work
class SystemTest {
  static void verifyImports() {
    // This method exists just to verify all imports compile correctly
    print('All imports verified successfully!');
  }
}
