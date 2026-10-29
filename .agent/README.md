# .agent/

The agent orchestrator's worker recipe for this repo:

- `job.sh` — sourced by the orchestrator's `worker/run-job.sh`. Defines the hooks (`prepare`, `setup`, `serve_cmd`, `lint`, `run_tests`, `e2e`, `preview_build`) that install, serve and test the app on a worker. Plain sourceable bash: no `set -e`, no top-level side effects beyond exports.
- `prompt.md` — environment notes appended to the agent's prompt. Placeholders like `{app_url}` and `{pg_port}` are filled in by the orchestrator.

The orchestrator reads these from `main`, so changes only take effect once merged. Ship setup changes in the same PR as the code that needs them.
