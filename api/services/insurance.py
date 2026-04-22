from typing import Dict, List, Tuple

from models import Message, PromptRequest,UserContext
from utils.call_llm import call_llm
from utils.guard import is_insurance_related, BLOCKED_REPLY
from utils.intent import detect_intent, Intent, BLOCKED_REPLY
from utils.context_builder import build_context_block

# ─────────────────────────────────────────────────────────────────────────────
# Insurance System Prompt
# ─────────────────────────────────────────────────────────────────────────────
INSURANCE_SYSTEM_PROMPT = """You are an expert insurance assistant for a fintech platform.
You help users:
- Understand available insurance packages and their coverage details
- Manage their insured assets (vehicles, property, equipment, etc.)
- Submit and track contract requests
- Understand premium calculations (base price × risk multiplier)
- Explain policy terms, deductibles, and claim procedures

Be concise, professional, and empathetic. Ask clarifying questions when needed.
If a user asks something outside insurance, politely redirect them."""

# Per-user in-memory conversation store  (user_id → [{role, content}, ...])
user_memories: Dict[int, List[dict]] = {}

async def get_insurance_reply(
    user_id: int,
    message: str,
    context: UserContext,
    history: List[Message] = None,
) -> Tuple[str, int]:

    # 1. Merged guard + intent — one LLM call
    intent = await detect_intent(message)

    if intent == Intent.BLOCKED:
        return BLOCKED_REPLY, len(history) if history else 0

    # 2. Slice and format only the relevant data
    context_block = build_context_block(intent, context.model_dump())

    # 3. Inject context into the user prompt
    enriched_prompt = (
        f"{context_block}\n\nUser question: {message}"
        if context_block else message
    )

    # 4. Determine history to use
    # If history is passed in the request, use it (master).
    # Otherwise, fall back to in-memory store.
    if history is not None:
        history_messages = history
    else:
        if user_id not in user_memories:
            user_memories[user_id] = []
        history_messages = [Message(**m) for m in user_memories[user_id]]

    # 5. Call main LLM
    request = PromptRequest(
        prompt=enriched_prompt,
        history=history_messages,
        system_prompt=INSURANCE_SYSTEM_PROMPT,
        temperature=0.7,
        max_tokens=1024,
    )

    response = await call_llm(request)

    # Update in-memory fallback
    user_memories[user_id] = [m.model_dump() for m in response.history][-20:]

    return response.reply, len(response.history)


def reset_user_memory(user_id: int) -> None:
    """Clear the conversation memory for the given user."""
    user_memories.pop(user_id, None)


def get_user_history(user_id: int) -> List[dict]:
    """Return the stored conversation history for the given user."""
    return user_memories.get(user_id, [])
