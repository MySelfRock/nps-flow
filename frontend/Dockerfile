FROM node:18-alpine

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install || true

# Copy app files
COPY . .

EXPOSE 3000

CMD ["npm", "start"]
