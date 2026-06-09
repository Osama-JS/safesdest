import 'dart:io';

void main() {
  final file = File('app_safedest_customer/lib/Languages/Messages.dart');
  final lines = file.readAsLinesSync();
  
  final List<String> out = [];
  Map<String, bool> seen = {};
  
  for (var line in lines) {
    if (RegExp(r"^\s*'([a-z]{2})':\s*\{").hasMatch(line)) {
      seen = {};
    }
    
    final match = RegExp(r'^\s*"([^"]+)":').firstMatch(line);
    if (match != null) {
      final key = match.group(1)!;
      if (seen.containsKey(key)) {
        continue; // duplicate key
      } else {
        seen[key] = true;
      }
    }
    
    out.add(line);
  }
  
  file.writeAsStringSync(out.join('\n'));
}
