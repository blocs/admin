# Process::input(json_encode())->run(['python', 'chunk.py'])

import sys
import json
from langchain_text_splitters import CharacterTextSplitter

text_splitter = CharacterTextSplitter(
    chunk_size=int(sys.argv[1]),
    chunk_overlap=int(sys.argv[2]),
    separator="\n",
)

text = json.loads(input().strip())
texts = text_splitter.split_text(text)

print(json.dumps(texts))
