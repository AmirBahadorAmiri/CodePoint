/**
 * Language picker data — a direct port of read.php's $langGroups and
 * $langLabels. The viewer's <select> is built from this list, and the
 * highlight.js bundle only carries its "common" grammars (~36), so the
 * viewer script fetches whatever it misses from the local vendor folder.
 */

var CodePointLangs = {
    groups: {
        'وب و نشانه‌گذاری': [
            'html', 'xml', 'css', 'scss', 'less', 'stylus', 'haml', 'twig', 'handlebars',
            'django', 'php-template', 'markdown', 'asciidoc', 'latex', 'xquery', 'http'
        ],
        'اسکریپت': [
            'javascript', 'typescript', 'python', 'ruby', 'php', 'perl', 'lua', 'r',
            'matlab', 'julia', 'scala', 'kotlin', 'groovy', 'clojure', 'haskell', 'elixir',
            'erlang', 'lisp', 'scheme', 'ocaml', 'fsharp', 'nim', 'coffeescript', 'elm',
            'powershell', 'awk', 'vim'
        ],
        'کامپایلی و سیستمی': [
            'c', 'cpp', 'csharp', 'vbnet', 'java', 'swift', 'dart', 'go', 'rust',
            'objectivec', 'arduino', 'smali', 'd', 'fortran', 'ada', 'smalltalk',
            'x86asm', 'llvm', 'wasm', 'glsl', 'verilog', 'vhdl'
        ],
        'داده و پیکربندی': [
            'json', 'yaml', 'ini', 'properties', 'sql', 'pgsql', 'graphql', 'protobuf',
            'thrift', 'diff', 'dns', 'accesslog', 'gherkin'
        ],
        'ترمینال و زیرساخت': [
            'bash', 'shell', 'dos', 'makefile', 'cmake', 'apache', 'nginx', 'dockerfile',
            'gradle', 'nix'
        ],
        'سایر': ['excel', 'plaintext']
    },

    // only the entries whose grammar name is uglier than the label people expect
    labels: {
        'csharp': 'C#', 'cpp': 'C++', 'fsharp': 'F#', 'vbnet': 'VB.NET',
        'objectivec': 'Objective-C', 'php-template': 'PHP Template',
        'html': 'HTML', 'xml': 'XML', 'css': 'CSS', 'scss': 'SCSS', 'sql': 'SQL',
        'pgsql': 'PostgreSQL', 'json': 'JSON', 'yaml': 'YAML', 'ini': 'INI',
        'php': 'PHP', 'graphql': 'GraphQL', 'http': 'HTTP', 'dns': 'DNS',
        'apache': 'Apache', 'nginx': 'Nginx', 'cmake': 'CMake', 'nix': 'Nix',
        'wasm': 'WebAssembly', 'dockerfile': 'Dockerfile', 'gradle': 'Gradle',
        'x86asm': 'x86 ASM', 'llvm': 'LLVM', 'glsl': 'GLSL', 'vhdl': 'VHDL',
        'latex': 'LaTeX', 'xquery': 'XQuery', 'asciidoc': 'AsciiDoc', 'haml': 'Haml',
        'properties': 'Java Properties', 'plaintext': 'بدون هایلایت'
    },

    label(name) {
        return this.labels[name] || name;
    }
};

if (typeof window !== 'undefined') {
    window.CodePointLangs = CodePointLangs;
} else {
    self.CodePointLangs = CodePointLangs;
}
