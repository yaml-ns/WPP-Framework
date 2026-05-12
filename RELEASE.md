# Release Process

WPP Framework uses Semantic Versioning.

- Patch: bug fixes and documentation changes that do not change public behavior.
- Minor: new backwards-compatible APIs, providers, generators or config options.
- Major: breaking API, config, behavior or minimum PHP/WordPress requirement changes.

Pre-`1.0.0`, APIs may still change, but every breaking change must be recorded in `CHANGELOG.md`.

## Checklist

1. Update `CHANGELOG.md`.
2. Run the local test suite:

```bash
docker compose run --rm tests
docker compose run --rm tests composer validate --strict
```

3. Verify generated files and examples still use current conventions.
4. Commit the release changes.
5. Create an annotated Git tag:

```bash
git tag -a v0.9.0 -m "Release v0.9.0"
git push origin main --tags
```

6. If publishing to Packagist, verify that the package is connected to the repository and that the new tag is visible.

## Stability Target

- `0.9.x`: pilot production usage in internal plugins.
- `1.0.0`: public API freeze for bootstrap, providers, routing, validation, repositories, views and CLI generators.
